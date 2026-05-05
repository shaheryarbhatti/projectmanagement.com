<?php

namespace App\Services\ExcelInsight;

use App\Models\ChatMessage;
use App\Models\DatabaseSmartRecord;
use App\Models\PivotTableEntry;
use App\Models\SuspensionResumptionRecord;
use App\Models\User;
use App\Models\WorkbookUpload;
use Illuminate\Support\Str;

class ChatAssistantService
{
    public function __construct(
        private readonly AiFallbackService $aiFallbackService,
        private readonly AnalyticsService $analyticsService,
    ) {
    }

    public function answer(User $user, string $question, ?string $scope = null): array
    {
        $latestUpload = WorkbookUpload::query()->latest('processed_at')->latest('id')->first();
        $directPayload = [];
        
        // 1. Fetch Raw Data Context (The "Knowledge")
        $dbData = $this->directAnswer($question, $directPayload, $scope);
        $id = $this->extractIdentifier(strtolower($question));
        $record = $id ? $this->findRecord($id, $scope) : null;

        // 2. Build the Knowledge Context for the AI
        $systemContext = $this->analyticsService->summaryText();
        
        if ($scope) {
            $systemContext .= "\n\nCRITICAL: The user is currently viewing the [" . strtoupper($scope) . "] section. Prioritize answers from this specific context.";
        }

        if ($record) {
            $systemContext .= "\n\n--- MATCHED RECORD ---\n" . json_encode($record->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        if ($dbData) {
            $systemContext .= "\n\n--- ANALYTICAL FINDINGS ---\n" . $dbData;
        }

        // 3. Try Conversational AI First
        $ai = null;
        if (config('excel_insights.openai.api_key') || config('excel_insights.grok.api_key') || config('excel_insights.groq.api_key')) {
            $history = ChatMessage::query()->where('user_id', $user->id)->latest('id')->limit(5)->get()->reverse()->flatMap(fn ($m) => [['role' => 'user', 'content' => $m->question],['role' => 'assistant', 'content' => $m->answer]])->values()->all();
            $ai = $this->aiFallbackService->reply($question, $history, $systemContext);
        }

        // 4. LOCAL WISDOM ENGINE
        if (!$ai) {
            $answer = $this->generateLocalResponse($question, $dbData, $record);
            $provider = 'local_wisdom';
            $source = 'built_in';
        } else {
            $answer = $ai['answer'];
            $provider = $ai['provider'];
            $source = 'ai_enhanced';
        }

        // 5. Save and Return
        $message = ChatMessage::create([
            'user_id' => $user->id,
            'upload_batch_id' => $latestUpload?->id,
            'question' => $question,
            'answer' => $answer,
            'provider' => $provider,
            'source' => $source,
            'context_payload' => ['direct_payload' => $directPayload, 'scope' => $scope],
        ]);

        return [
            'answer' => $message->answer,
            'provider' => $message->provider,
            'source' => $message->source,
            'payload' => $directPayload,
            'created_at' => $message->created_at->format('h:i A'),
        ];
    }

    /**
     * The Local Wisdom Engine: Generates human-like responses 100% locally and for free.
     */
    private function generateLocalResponse(string $question, ?string $dbData, ?object $record): string
    {
        if ($dbData) return $dbData;

        return "**Project Management Intelligence Assistant** \n\n**Quick Queries:**\n- 'Overview of all suspended projects'\n- 'Total allocated budget for the Engineering department'\n- 'Full project disclosure for PO 4300000589'";
    }

    private function directAnswer(string $question, array &$payload = [], ?string $scope = null): ?string
    {
        $normalized = strtolower(trim($question));
        if ($normalized === '') return null;

        // 1. SPECIFIC PROJECT LOOKUP (High Priority)
        // If the user mentions a PO, Ref, or Project Name, we find that record FIRST
        if ($scope !== 'pivot') {
            $id = $this->extractIdentifier($normalized);
            if ($id) {
                $record = $this->findRecord($id, $scope);
                if ($record) return $this->formatRecordResponse($record, $normalized, $id);
            }
        }

        // 2. Charts/Stats (Global or Scoped)
        if ($this->matches($normalized, ['stat', 'graph', 'chart', 'summary', 'dashboard'])) {
            $data = $this->analyticsService->dashboardData();
            $payload['chart'] = ['type' => 'pie', 'data' => $data['charts']['statusDistribution'], 'title' => 'Project Status distribution'];
            return "### 📊 Real-time Dashboard Summary\n\nSummary generated for **" . number_format($data['stats']['total_records']) . "** records.";
        }

        // 3. Complex Queries (Aggregations/Counts/Filters)
        $complex = $this->handleComplexQuery($normalized, $scope);
        if ($complex) return $complex;

        if (!$scope || $scope === 'pivot') {
            return $this->searchPivot($normalized);
        }

        return null;
    }

    private function handleComplexQuery(string $normalized, ?string $scope = null): ?string
    {
        // 1. Determine Target Tables to Scan
        $tables = ['database_smart_records', 'suspension_resumption_records'];
        if ($scope === 'smart') $tables = ['database_smart_records'];
        if ($scope === 'suspension') $tables = ['suspension_resumption_records'];

        $bestResults = null;
        $highestConfidence = 0;

        foreach ($tables as $targetTable) {
            $cols = \Illuminate\Support\Facades\Schema::getColumnListing($targetTable);
            $query = \Illuminate\Support\Facades\DB::table($targetTable)->distinct();
            $filtersApplied = [];
            $confidence = 0;

            // 2. FUZZY FILTER ENGINE: Word-overlap scoring
            $actionWords = ['how many', 'count', 'total', 'sum', 'budget', 'value', 'amount', 'duration', 'days', 'cost', 'paid'];
            $excludeCols = ['id', 'user_id', 'upload_batch_id', 'created_at', 'updated_at', 'source_row', 'brief_description', 'engineering_status_update', 'procurement_status_update', 'construction_status_update', 'weekly_lookahead', 'issues_concerns', 'risks', 'data_issue'];
            $qWords = array_filter(explode(' ', preg_replace('/[^a-z0-9 ]/i', ' ', $normalized)), fn($w) => strlen($w) > 3 && !in_array($w, $actionWords));
            
            foreach ($cols as $col) {
                if (in_array(strtolower($col), $excludeCols)) continue;
                
                // Optimized unique value scanning (Only short/categorical values)
                $uniques = \Illuminate\Support\Facades\DB::table($targetTable)->whereNotNull($col)->where($col, '!=', '')->distinct()->pluck($col);
                foreach ($uniques as $val) {
                    $valStr = strtolower((string)$val);
                    if (strlen($valStr) > 100) continue; // Skip long narrative content as filter keys

                    $vWords = array_filter(explode(' ', preg_replace('/[^a-z0-9 ]/i', ' ', $valStr)), fn($w) => strlen($w) > 3);
                    
                    // Score the match based on word overlap
                    $matchCount = 0;
                    foreach ($vWords as $vw) { 
                        if (in_array($vw, $qWords)) $matchCount++; 
                    }
                    
                    if ($matchCount >= 2 || (count($vWords) === 1 && in_array($valStr, $qWords))) {
                        $query->where($col, $val);
                        $displayVal = strlen($val) > 50 ? substr($val, 0, 47) . '...' : $val;
                        $filtersApplied[] = ucwords(str_replace('_', ' ', $col)) . ": **$displayVal**";
                        $confidence += ($matchCount * 10);
                        break;
                    }
                }
            }

            // 3. ACTION & AGGREGATION
            $action = 'list';
            if ($this->matches($normalized, ['how many', 'count', 'number of'])) $action = 'count';
            if ($this->matches($normalized, ['total', 'sum', 'budget', 'value', 'amount', 'duration', 'days', 'cost', 'paid'])) $action = 'sum';

            if ($action !== 'list' || !empty($filtersApplied) || $this->matches($normalized, ['all', 'list', 'show'])) {
                $count = $query->count();
                $res = "";
                $filterStr = !empty($filtersApplied) ? "Filtered by " . implode(', ', $filtersApplied) : "Global Results";

                if ($action === 'count') {
                    $res = "### 🔢 Record Count\n$filterStr\n\nTotal records identified: **" . number_format($count) . "**";
                } elseif ($action === 'sum') {
                    $sumCol = null;
                    $cleanQuery = preg_replace('/[^a-z0-9]/', '', $normalized);
                    
                    // A. Absolute Match (Ignoring spaces/underscores/special chars)
                    foreach ($cols as $candidate) {
                        $cleanCol = preg_replace('/[^a-z0-9]/', '', strtolower($candidate));
                        if (str_contains($cleanQuery, $cleanCol) && $cleanCol !== '') {
                            $sumCol = $candidate;
                            $confidence += 50; // MAJOR CONFIDENCE BOOST for finding the right column
                            break;
                        }
                    }

                    // B. Keyword matching (If no absolute match)
                    if (!$sumCol) {
                        foreach ($cols as $candidate) {
                            $cName = strtolower(str_replace('_', ' ', $candidate));
                            if ($this->matches($normalized, explode(' ', $cName))) {
                                if ($this->matches($cName, ['amount', 'value', 'paid', 'cost', 'budget', 'duration'])) {
                                    $sumCol = $candidate; 
                                    $confidence += 25; // Moderate boost
                                    break;
                                }
                            }
                        }
                    }

                    // C. Defaults
                    if (!$sumCol) {
                        $defaults = ($targetTable === 'suspension_resumption_records') ? ['suspension_duration_days'] : ['allocated_project_amount', 'budget'];
                        foreach ($defaults as $d) { if (in_array($d, $cols)) { $sumCol = $d; break; } }
                    }

                    if ($sumCol) {
                        $total = \Illuminate\Support\Facades\DB::table($targetTable)->sum($sumCol);
                        $label = ucwords(str_replace('_', ' ', $sumCol));
                        $res = "### 📊 Analytical Summary\n$filterStr\n\nThe total **$label** is: **" . number_format($total, 2) . "**";
                    }
                } else {
                    $results = $query->limit(5)->get();
                    $res = "### 📋 Data Analysis: " . ucwords(str_replace('_', ' ', $targetTable)) . "\n$filterStr\n\n";
                    if ($targetTable === 'suspension_resumption_records') {
                        $res .= "| Project Name | Reason | Date |\n| :--- | :--- | :--- |\n";
                        foreach ($results as $row) { $res .= "| " . ($row->project_name ?? 'N/A') . " | " . ($row->suspension_reason ?? 'N/A') . " | " . $this->formatValue($row->suspension_date ?? null, 'suspension_date') . " |\n"; }
                    } else {
                        $res .= "| Project Name | Status | Budget |\n| :--- | :--- | :--- |\n";
                        foreach ($results as $row) {
                            $budget = $row->allocated_project_amount ?? $row->budget ?? $row->po_value ?? 0;
                            $res .= "| " . ($row->project_name ?? 'N/A') . " | " . ($row->project_status ?? 'N/A') . " | " . number_format($budget, 2) . " |\n";
                        }
                    }
                    if ($count > 5) $res .= "\n*...and " . ($count - 5) . " more records.*";
                }

                if ($confidence >= $highestConfidence) {
                    $highestConfidence = $confidence;
                    $bestResults = $res;
                }
            }
        }

        return $bestResults;
    }

    private function extractIdentifier(string $normalized): ?array
    {
        // 1. Quoted Project Name Detection (e.g., "North Terminal...")
        if (preg_match('/["\']([^"\']{5,})["\']/i', $normalized, $matches)) {
            $pName = $matches[1];
            $exists = DatabaseSmartRecord::query()->where('project_name', 'like', "%$pName%")->exists() || 
                     SuspensionResumptionRecord::query()->where('project_name', 'like', "%$pName%")->exists();
            if ($exists) return ['type' => 'project_name', 'value' => $pName];
        }

        // 2. Explicit PO/Ref Detection
        if (preg_match('/po\s*-?\s*(\d+)/i', $normalized, $matches)) {
            return ['type' => 'po_no', 'value' => $matches[1]];
        }
        if (preg_match('/ref\s*-?\s*([a-z0-9\-]+)/i', $normalized, $matches)) {
            return ['type' => 'ref', 'value' => strtoupper($matches[1])];
        }

        // 3. Smart Project Name Extraction (Bypass need for 'project' keyword)
        $words = array_filter(explode(' ', preg_replace('/[^a-z0-9 ]/i', ' ', $normalized)), fn($w) => strlen($w) > 3);
        if (count($words) >= 2) {
            $bestMatch = null;
            $maxScore = 0;
            
            // Get all unique project names
            $all = \Illuminate\Support\Facades\DB::table('database_smart_records')->select('project_name')
                ->union(\Illuminate\Support\Facades\DB::table('suspension_resumption_records')->select('project_name'))
                ->distinct()->get();

            foreach ($all as $p) {
                $score = 0;
                $pLower = strtolower($p->project_name);
                foreach ($words as $word) { 
                    if (str_contains($pLower, $word)) $score++; 
                }
                
                // Extra weight if the entire project name is inside the question
                if (str_contains($normalized, $pLower)) $score += 2;

                if ($score > $maxScore) {
                    $maxScore = $score;
                    $bestMatch = $p->project_name;
                }
            }
            
            // If we have a strong match, use it
            if ($maxScore >= 3 || (count($words) >= 2 && $maxScore >= count($words))) {
                return ['type' => 'project_name', 'value' => $bestMatch];
            }
        }

        return null;
    }

    private function findRecord(array $id, ?string $scope = null): ?object
    {
        $type = $id['type'];
        $value = $id['value'];

        // 1. Search Smart Records
        if (!$scope || $scope === 'smart') {
            if (\Illuminate\Support\Facades\Schema::hasColumn('database_smart_records', $type)) {
                $smart = DatabaseSmartRecord::query()->where($type, 'like', "%$value%")->first();
                if ($smart) return $smart;
            }
        }

        // 2. Search Suspension Records
        if (!$scope || $scope === 'suspension') {
            $col = ($type === 'po_no' || $type === 'ref') ? 'po' : $type;
            if (\Illuminate\Support\Facades\Schema::hasColumn('suspension_resumption_records', $col)) {
                return SuspensionResumptionRecord::query()->where($col, 'like', "%$value%")->first();
            }
        }

        return null;
    }

    private function formatRecordResponse($record, string $normalized, array $id): string
    {
        $isSuspension = $record instanceof SuspensionResumptionRecord;
        $title = $isSuspension ? "Suspension History" : "Project Detail";
        $tableName = $isSuspension ? 'suspension_resumption_records' : 'database_smart_records';
        
        $cols = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
        $matches = [];
        $wantsFull = $this->matches($normalized, ['complete detail', 'full detail', 'all attribute', 'show all', 'everything', 'entire', 'full disclosure']);

        foreach ($cols as $col) {
            if (in_array($col, ['id', 'user_id', 'upload_batch_id', 'created_at', 'updated_at', 'source_row'])) continue;
            
            $hName = strtolower(str_replace('_', ' ', $col));
            $hWords = explode(' ', $hName);
            
            // Check for explicit mention in question (Whole word matching for short terms)
            $isExplicitMatch = false;
            if (strlen($hName) <= 3) {
                $isExplicitMatch = preg_match('/\b' . preg_quote($hName, '/') . '\b/i', $normalized);
            } else {
                $isExplicitMatch = str_contains($normalized, $hName);
            }

            if (!$isExplicitMatch) {
                $matchCount = 0;
                foreach ($hWords as $word) { 
                    if (strlen($word) > 3 && str_contains($normalized, $word)) $matchCount++; 
                }
                if ($matchCount >= 2) $isExplicitMatch = true;
            }

            if ($isExplicitMatch) {
                $matches[$hName] = $this->formatValue($record->{$col}, $col);
            }
        }

        // 1. If specific data requested AND NOT asking for 'full detail'
        if (!empty($matches) && !$wantsFull) {
            $res = "### 📑 Requested Details: {$record->project_name}\n\n";
            foreach ($matches as $name => $val) {
                $res .= "- **" . ucwords($name) . "**: $val\n";
            }
            return $res;
        }

        // 2. SHOW COMPLETE DATA (Only if explicitly requested)
        if ($wantsFull) {
            $res = "### 📑 Full Project Disclosure: {$record->project_name}\n\n| Attribute | Value |\n| :--- | :--- |\n";
            foreach ($cols as $col) {
                if (in_array($col, ['id', 'user_id', 'upload_batch_id', 'created_at', 'updated_at', 'source_row'])) continue;
                $res .= "| **" . ucwords(str_replace('_', ' ', $col)) . "** | " . $this->formatValue($record->{$col}, $col) . " |\n";
            }
            return $res;
        }

        // 3. FALLBACK: Show Concise Overview (Standard Query)
        $res = "### 📑 Project Overview: {$record->project_name}\n\n| Attribute | Value |\n| :--- | :--- |\n";
        $essential = $isSuspension 
            ? ['po', 'suspension_date', 'suspension_reason', 'resumption_date', 'status_of_resumption']
            : ['po_no', 'project_status', 'budget', 'allocated_project_amount', 'department'];

        foreach ($essential as $key) {
            if (isset($record->{$key})) {
                $label = ucwords(str_replace('_', ' ', $key));
                $res .= "| **$label** | " . $this->formatValue($record->{$key}, $key) . " |\n";
            }
        }
        
        return $res;
    }

    private function formatValue($val, string $column): string
    {
        if (empty($val) && $val !== 0 && $val !== '0') return "_Not specified_";

        $colLower = strtolower($column);

        // 1. Precise Date Detection
        $isDateCol = str_contains($colLower, 'date') || str_ends_with($colLower, '_at') || str_contains($colLower, 'finish') || str_contains($colLower, 'start') || str_contains($colLower, 'updated');
        
        if ($isDateCol && !is_numeric($val)) {
            try { return \Carbon\Carbon::parse($val)->format('d M Y'); } catch (\Exception $e) { }
        }

        // 2. Numeric Formatters
        if (is_numeric($val)) {
            // Excel Timestamp support
            if ($isDateCol && $val > 10000 && $val < 100000) {
                try { return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val))->format('d M Y'); } catch(\Exception $e) {}
            }

            // Percentages and Ratios
            if (str_contains($colLower, '%') || str_contains($colLower, 'progress') || str_contains($colLower, 'pct') || str_contains($colLower, 'spi') || str_contains($colLower, 'cpi')) {
                return number_format((float) $val, 2) . (str_contains($colLower, '%') || str_contains($colLower, 'progress') ? '%' : '');
            }

            // Financials / Large Numbers
            $isFinancial = str_contains($colLower, 'value') || str_contains($colLower, 'amount') || str_contains($colLower, 'budget') || 
                           str_contains($colLower, 'paid') || str_contains($colLower, 'invoiced') || str_contains($colLower, 'remaining') ||
                           in_array($colLower, ['ev', 'pv', 'ac', 'cost']);

            if ($isFinancial || abs($val) > 999) {
                return number_format((float) $val, 2);
            }
        }

        return (string) $val;
    }

    private function searchPivot(string $normalized): ?string
    {
        $words = explode(' ', $normalized);
        foreach ($words as $word) {
            if (strlen($word) < 4) continue;
            $entry = PivotTableEntry::query()->where('metric_title', 'like', "%{$word}%")->orWhere('row_label', 'like', "%{$word}%")->first();
            if ($entry) {
                return "### 📈 Pivot Table Insight\n**Metric:** {$entry->metric_title}\n**Value:** " . ($entry->value_text ?: $entry->value_numeric);
            }
        }
        return null;
    }

    private function matches(string $normalized, array $needles): bool
    {
        foreach ($needles as $needle) { if (str_contains($normalized, $needle)) return true; }
        return false;
    }
}
