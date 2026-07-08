<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OllamaAiService
{
    protected $baseUrl;
    protected $model;
    protected $timeout;
    protected $apiKey;
    protected $isNvidia = false;

    public function __construct()
    {
        $this->apiKey = config('services.nvidia.api_key');
        if (!empty($this->apiKey)) {
            $this->isNvidia = true;
            $this->baseUrl = config('services.nvidia.base_url', 'https://integrate.api.nvidia.com/v1');
            $this->model = config('services.nvidia.model', 'meta/llama-3.1-8b-instruct');
            $this->timeout = 10;
        } else {
            $this->baseUrl = config('services.ollama.base_url', 'http://localhost:11434');
            $this->model = config('services.ollama.model', 'llama3.1:8b');
            $this->timeout = config('services.ollama.timeout', 2);
        }
    }

    /**
     * Helper method to call the LLM endpoint (supporting both Ollama and NVIDIA).
     */
    protected function callAiEndpoint(string $prompt): ?array
    {
        if ($this->isServiceOffline()) {
            return null;
        }

        $url = $this->isNvidia 
            ? rtrim($this->baseUrl, '/') . '/chat/completions'
            : rtrim($this->baseUrl, '/') . '/api/chat';

        $headers = [
            'Content-Type' => 'application/json',
        ];
        if ($this->isNvidia) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
        ];

        if ($this->isNvidia) {
            $payload['temperature'] = 0.2;
            $payload['top_p'] = 0.7;
            $payload['max_tokens'] = 2048;
            $payload['response_format'] = ['type' => 'json_object'];
        } else {
            $payload['stream'] = false;
            $payload['format'] = 'json';
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->post($url, $payload);

            if ($response->failed()) {
                Log::error('AI Service request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('AI API returned error status.');
            }

            $result = $response->json();
            
            $textResponse = $this->isNvidia
                ? ($result['choices'][0]['message']['content'] ?? null)
                : ($result['message']['content'] ?? null);

            if (empty($textResponse)) {
                throw new Exception('Empty response returned from the AI model.');
            }

            $data = json_decode($textResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON returned by the model: ' . json_last_error_msg());
            }

            return $data;

        } catch (Exception $e) {
            Log::error('AI Endpoint Exception: ' . $e->getMessage());
            $this->markServiceOffline();
            return null;
        }
    }

    /**
     * Generate AI insights based on deterministic performance metrics.
     *
     * @param array $metrics
     * @return array
     */
    public function generateInsights(array $metrics): array
    {
        $prompt = $this->buildPrompt($metrics);
        $insights = $this->callAiEndpoint($prompt);

        if (!$insights) {
            return $this->getMockInsights($metrics);
        }

        // Ensure all required fields exist
        $requiredKeys = ['summary', 'strengths', 'weaknesses', 'risks', 'recommendations', 'team_health'];
        foreach ($requiredKeys as $key) {
            if (!isset($insights[$key])) {
                $insights[$key] = ($key === 'summary' || $key === 'team_health') ? '' : [];
            }
        }

        return $insights;
    }

    /**
     * Build prompt with instructions preventing recalculations.
     */
    protected function buildPrompt(array $metrics): string
    {
        $metricsJson = json_encode([
            'manager_score' => $metrics['manager_score'] ?? null,
            'completion_rate' => $metrics['task_completion_rate'] ?? null,
            'deadline_adherence' => $metrics['deadline_adherence_rate'] ?? null,
            'productivity_score' => $metrics['productivity_score'] ?? null,
            'consistency_score' => $metrics['consistency_score'] ?? null,
            'metrics_breakdown' => $metrics['metrics_breakdown'] ?? null,
        ], JSON_PRETTY_PRINT);

        return <<<PROMPT
You are an AI Manager Agent. Your role is to analyze dynamic performance metrics of a manager's team and provide high-quality qualitative insights.

IMPORTANT INSTRUCTION:
Do NOT calculate or modify the scores yourself. Rely strictly on the provided scores and metrics. Do not try to re-compute them.

Here are the metrics for this period:
{$metricsJson}

Analyze this data and return a JSON object containing:
- "summary": A brief, professional overview summarizing the team's performance for this period.
- "strengths": An array of strings outlining specific data-backed strengths observed from the metrics.
- "weaknesses": An array of strings highlighting specific weaknesses where performance dropped.
- "risks": An array of strings warning about potential risks (e.g. bottleneck, burn-out, or missing deadlines) if the trends continue.
- "recommendations": An array of actionable, high-quality advice for the manager to address the identified issues.
- "team_health": A high-level description of the team's overall health (e.g., "Excellent", "Cohesive but at risk of fatigue", "Needs Attention").
PROMPT;
    }

    /**
     * Fallback mock insights generation if Ollama fails or is offline.
     */
    protected function getMockInsights(array $metrics): array
    {
        $score = $metrics['manager_score'] ?? 0;
        
        if ($score >= 80) {
            $health = 'Excellent';
            $summary = 'The team performed outstandingly during this period. Task completion and productivity metrics are high, representing high engagement and quality outputs.';
            $strengths = ['High overall manager score of ' . $score . '%', 'Outstanding task completion rate.'];
            $weaknesses = ['Minor deviations in individual daily logged hours.'];
            $risks = ['Slight risk of employee fatigue if high productivity remains unmonitored.'];
            $recommendations = ['Acknowledge and celebrate the team accomplishments.', 'Continue monitoring workloads to sustain the balance.'];
        } elseif ($score >= 60) {
            $health = 'Healthy';
            $summary = 'The team shows solid performance. While task completion remains stable, some secondary areas like consistency or deadline adherence can be further optimized.';
            $strengths = ['Good operational alignment and steady productivity.', 'Consistent core task delivery.'];
            $weaknesses = ['Inconsistent daily log entries.', 'A few tasks falling behind schedule.'];
            $risks = ['Risks of missed milestones if deadline adherence drops further.'];
            $recommendations = ['Run quick daily standups to identify bottlenecks.', 'Encourage employees to update task statuses on time.'];
        } else {
            $health = 'Needs Attention';
            $summary = 'The team metrics reflect sub-optimal performance for this period. Immediate managerial intervention is recommended to support task completion and attendance adherence.';
            $strengths = ['Basic attendance logging is maintained.'];
            $weaknesses = ['Low task completion rate.', 'Low productivity score relative to expectations.', 'High variance in daily work consistency.'];
            $risks = ['High project delay risks.', 'Low team morale or engagement.'];
            $recommendations = ['Conduct one-on-one sessions with team members to discuss challenges.', 'Review task distribution and deadlines to avoid bottlenecks.'];
        }

        return [
            'summary' => $summary,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'risks' => $risks,
            'recommendations' => $recommendations,
            'team_health' => $health
        ];
    }

    /**
     * Generate AI insights based on deterministic employee performance metrics.
     */
    public function generateEmployeeInsights(\App\Models\User $employee, array $metrics): array
    {
        $prompt = $this->buildEmployeePrompt($employee, $metrics);
        $insights = $this->callAiEndpoint($prompt);

        if (!$insights) {
            return $this->getMockEmployeeInsights($employee, $metrics);
        }

        // Ensure all required fields exist
        $requiredKeys = ['summary', 'strengths', 'weaknesses', 'risks', 'recommendations', 'performance_rating'];
        foreach ($requiredKeys as $key) {
            if (!isset($insights[$key])) {
                $insights[$key] = ($key === 'summary' || $key === 'performance_rating') ? '' : [];
            }
        }

        return $insights;
    }

    protected function buildEmployeePrompt(\App\Models\User $employee, array $metrics): string
    {
        $metricsJson = json_encode([
            'developer_score' => $metrics['developer_score'] ?? null,
            'completion_rate' => $metrics['task_completion_rate'] ?? null,
            'delivery_speed_score' => $metrics['delivery_speed_score'] ?? null,
            'code_quality_score' => $metrics['code_quality_score'] ?? null,
            'reviews_score' => $metrics['reviews_score'] ?? null,
            'productivity_score' => $metrics['productivity_score'] ?? null,
            'consistency_score' => $metrics['consistency_score'] ?? null,
        ], JSON_PRETTY_PRINT);

        return <<<PROMPT
You are an AI Manager Agent. Your role is to analyze dynamic performance metrics of an employee named {$employee->name} and provide high-quality qualitative performance feedback.

IMPORTANT INSTRUCTION:
Do NOT calculate or modify the scores yourself. Rely strictly on the provided scores and metrics. Do not try to re-compute them.

Here are the employee's metrics for this period:
{$metricsJson}

Analyze this data and return a JSON object containing:
- "summary": A brief, professional summary of {$employee->name}'s performance.
- "strengths": An array of strings outlining specific strengths (e.g. fast task delivery, high completion rate).
- "weaknesses": An array of strings highlighting specific areas where they can improve.
- "risks": An array of strings outlining risks (e.g. burnout, deadline slippage).
- "recommendations": An array of actionable, supportive advice for the manager to help this employee.
- "performance_rating": A high-level descriptor of the employee's rating (e.g. "Outstanding", "Proficient", "Needs Support").
PROMPT;
    }

    protected function getMockEmployeeInsights(\App\Models\User $employee, array $metrics): array
    {
        $score = $metrics['developer_score'] ?? 0;
        
        if ($score >= 85) {
            $rating = 'Outstanding';
            $summary = "{$employee->name} has shown outstanding performance during this period. They consistently exceed expectations in task execution and speed, making them a top contributor to the team.";
            $strengths = ["Excellent developer score of {$score}%", "High task completion and delivery speed.", "Consistent contribution across tasks."];
            $weaknesses = ["Hardly any areas of concern; continue maintaining high standards."];
            $risks = ["Risk of burnout due to sustained high output.", "Potential bottleneck if too many tasks are routed through them."];
            $recommendations = ["Consider mentoring roles or leadership opportunities for {$employee->name}.", "Monitor workloads closely to prevent high-performance exhaustion."];
        } elseif ($score >= 65) {
            $rating = 'Proficient';
            $summary = "{$employee->name} is a solid performer who meets expectations in core responsibilities. They deliver tasks reliably, but have opportunities to optimize their delivery speed and task scope consistency.";
            $strengths = ["Solid performance with developer score of {$score}%", "Consistent work logs.", "Reliable execution of standard tasks."];
            $weaknesses = ["Slight delays on complex deliverables.", "Daily hours worked have mild inconsistency."];
            $risks = ["Minor risk of task backlog accumulation if work pace drops.", "Slight decrease in engagement if task assignments become repetitive."];
            $recommendations = ["Run short check-ins to identify early blockers.", "Acknowledge progress and assign a mix of challenging and straightforward tasks."];
        } else {
            $rating = 'Needs Support';
            $summary = "{$employee->name}'s performance indicators are currently sub-optimal. They require direct supportive manager intervention to resolve work pace bottlenecks, log hours consistency, and task delivery delays.";
            $strengths = ["Maintains participation in basic log routines."];
            $weaknesses = ["Lower task completion rates.", "Low productivity score relative to team standards.", "Significant variance in work pace."];
            $risks = ["High risk of project release delays.", "Decline in individual morale."];
            $recommendations = ["Schedule a one-on-one session to discuss obstacles or training needs.", "Set smaller, short-term targets with frequent check-ins to build confidence."];
        }

        return [
            'summary' => $summary,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'risks' => $risks,
            'recommendations' => $recommendations,
            'performance_rating' => $rating
        ];
    }

    /**
     * Generate AI insights based on deterministic team metrics.
     */
    public function generateTeamInsights(\App\Models\Team $team, array $metrics): array
    {
        $prompt = $this->buildTeamPrompt($team, $metrics);
        $insights = $this->callAiEndpoint($prompt);

        if (!$insights) {
            return $this->getMockTeamInsights($team, $metrics);
        }

        // Ensure all required fields exist
        $requiredKeys = ['summary', 'strengths', 'weaknesses', 'risks', 'recommendations', 'team_health'];
        foreach ($requiredKeys as $key) {
            if (!isset($insights[$key])) {
                $insights[$key] = ($key === 'summary' || $key === 'team_health') ? '' : [];
            }
        }

        return $insights;
    }

    protected function buildTeamPrompt(\App\Models\Team $team, array $metrics): string
    {
        $metricsJson = json_encode([
            'manager_score' => $metrics['manager_score'] ?? null,
            'completion_rate' => $metrics['task_completion_rate'] ?? null,
            'deadline_adherence' => $metrics['deadline_adherence_rate'] ?? null,
            'productivity_score' => $metrics['productivity_score'] ?? null,
            'consistency_score' => $metrics['consistency_score'] ?? null,
            'metrics_breakdown' => $metrics['metrics_breakdown'] ?? null,
        ], JSON_PRETTY_PRINT);

        return <<<PROMPT
You are an AI Manager Agent. Your role is to analyze dynamic performance metrics of a specific team named "{$team->name}" and provide high-quality qualitative insights.

IMPORTANT INSTRUCTION:
Do NOT calculate or modify the scores yourself. Rely strictly on the provided scores and metrics. Do not try to re-compute them.

Here are the team metrics for this period:
{$metricsJson}

Analyze this data and return a JSON object containing:
- "summary": A brief, professional overview summarizing the team's performance for this period.
- "strengths": An array of strings outlining specific data-backed strengths observed from the metrics.
- "weaknesses": An array of strings highlighting specific weaknesses where performance dropped.
- "risks": An array of strings warning about potential risks (e.g. bottleneck, burn-out, or missing deadlines) if the trends continue.
- "recommendations": An array of actionable, high-quality advice for the manager to address the identified issues.
- "team_health": A high-level description of the team's overall health (e.g., "Excellent", "Cohesive but at risk of fatigue", "Needs Attention").
PROMPT;
    }

    protected function getMockTeamInsights(\App\Models\Team $team, array $metrics): array
    {
        $score = $metrics['manager_score'] ?? 0;
        
        if ($score >= 80) {
            $health = 'Excellent';
            $summary = "The team '{$team->name}' performed outstandingly during this period. Task completion and productivity metrics are high, representing high engagement and quality outputs.";
            $strengths = ["High overall manager score of {$score}%", "Outstanding task completion rate.", "Excellent synergy and resource utilization."];
            $weaknesses = ["Minor deviations in individual daily logged hours."];
            $risks = ["Slight risk of employee fatigue if high productivity remains unmonitored."];
            $recommendations = ["Acknowledge and celebrate the team accomplishments.", "Continue monitoring workloads to sustain the balance."];
        } elseif ($score >= 60) {
            $health = 'Healthy';
            $summary = "The team '{$team->name}' shows solid performance. While task completion remains stable, some secondary areas like consistency or deadline adherence can be further optimized.";
            $strengths = ["Good operational alignment and steady productivity.", "Consistent core task delivery."];
            $weaknesses = ["Inconsistent daily log entries.", "A few tasks falling behind schedule."];
            $risks = ["Risks of missed milestones if deadline adherence drops further."];
            $recommendations = ["Run quick daily standups to identify bottlenecks.", "Encourage employees to update task statuses on time."];
        } else {
            $health = 'Needs Attention';
            $summary = "The team '{$team->name}' metrics reflect sub-optimal performance for this period. Immediate managerial intervention is recommended to support task completion and attendance adherence.";
            $strengths = ["Basic attendance logging is maintained."];
            $weaknesses = ["Low task completion rate.", "Low productivity score relative to expectations.", "High variance in daily work consistency."];
            $risks = ["High project delay risks.", "Low team morale or engagement."];
            $recommendations = ["Conduct one-on-one sessions with team members to discuss challenges.", "Review task distribution and deadlines to avoid bottlenecks."];
        }

        return [
            'summary' => $summary,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'risks' => $risks,
            'recommendations' => $recommendations,
            'team_health' => $health
        ];
    }

    /**
     * Generate AI predictive recommendations based on team health, active risks, and workload analysis.
     *
     * @param array $health
     * @param array $risks
     * @param array $workload
     * @return array
     */
    public function generatePredictiveInsights(array $health, array $risks, array $workload): array
    {
        $prompt = $this->buildPredictivePrompt($health, $risks, $workload);
        $insights = $this->callAiEndpoint($prompt);

        if (!$insights) {
            return $this->getMockPredictiveInsights($health, $risks, $workload);
        }

        // Validate structure
        $requiredKeys = ['team_recommendations', 'manager_action_plan', 'resource_allocation', 'risk_mitigation'];
        foreach ($requiredKeys as $key) {
            if (!isset($insights[$key]) || !is_array($insights[$key])) {
                $insights[$key] = [];
            }
        }

        return $insights;
    }

    /**
     * Build prompt for predictive analytics.
     */
    protected function buildPredictivePrompt(array $health, array $risks, array $workload): string
    {
        $healthJson = json_encode($health, JSON_PRETTY_PRINT);
        $risksJson = json_encode($risks, JSON_PRETTY_PRINT);
        $workloadJson = json_encode($workload, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are an AI Manager Agent specializing in predictive workforce intelligence.
Your task is to analyze structured metrics and generate proactive actions and recommendations.
DO NOT calculate or modify any scores or stats yourself. Rely strictly on the provided metrics.

Here is the team's data:

--- TEAM HEALTH DATA ---
{$healthJson}

--- ACTIVE RISK ALERTS ---
{$risksJson}

--- WORKLOAD ANALYSIS ---
{$workloadJson}

---
Analyze this data and return a JSON object containing:
- "team_recommendations": An array of strings of actionable steps to improve overall team health, attendance, and work consistency.
- "manager_action_plan": An array of strings of specific manager advice to resolve active employee risks.
- "resource_allocation": An array of strings of suggestions on task redistribution or hiring needs based on the workload analysis.
- "risk_mitigation": An array of strings of clear actions to mitigate burnout, deadlines, or engagement drops.
PROMPT;
    }

    /**
     * Fallback mock predictive recommendations.
     */
    protected function getMockPredictiveInsights(array $health, array $risks, array $workload): array
    {
        $healthScore = $health['team_health_score'] ?? 80;

        $teamRecs = [
            'Maintain transparent workload logs and encourage daily updates.',
            'Review expected weekly hour targets to align with team capacity.'
        ];
        
        $managerPlan = [
            'Conduct one-on-one reviews with employees showing medium/high risk alerts.',
            'Establish check-in routines to monitor task blocker dependencies.'
        ];

        $resourceAlloc = [
            'Distribute pending items from overloaded workers to underutilized team members.'
        ];

        $riskMitigation = [
            'Introduce mandatory cooldown periods or flexible working schedules to resolve burnout concerns.'
        ];

        // Customise slightly based on data
        if (!empty($workload['recommendations'])) {
            $resourceAlloc = $workload['recommendations'];
        }

        if (!empty($risks)) {
            foreach ($risks as $risk) {
                $employeeName = $risk['employee']['name'] ?? 'Team member';
                $type = $risk['risk_type'] ?? 'performance';
                if ($type === 'burnout') {
                    $riskMitigation[] = "Ensure {$employeeName} takes time off or reduce active task assignments immediately.";
                } elseif ($type === 'deadline') {
                    $riskMitigation[] = "Extend deadlines or delegate critical path tasks for {$employeeName} to prevent delivery delay.";
                }
            }
        }

        return [
            'team_recommendations' => $teamRecs,
            'manager_action_plan' => $managerPlan,
            'resource_allocation' => $resourceAlloc,
            'risk_mitigation' => $riskMitigation
        ];
    }

    /**
     * Check if the Ollama service is cached as offline.
     */
    protected function isServiceOffline(): bool
    {
        return \Illuminate\Support\Facades\Cache::get('ollama_offline', false);
    }

    /**
     * Cache the service as offline for 60 seconds to prevent connection spam.
     */
    protected function markServiceOffline(): void
    {
        \Illuminate\Support\Facades\Cache::put('ollama_offline', true, 60);
    }
}
