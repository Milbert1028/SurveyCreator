<?php /* DO NOT MODIFY THIS FILE - RESTORED BACKUP */ error_reporting(E_ALL); ini_set('display_errors', 1); ?>require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to view responses", "warning");
    redirect('/auth/login.php');
}

$survey_id = $_GET['id'] ?? null;
if (!$survey_id) {
    flash_message("Survey ID is required", "danger");
    redirect('/dashboard');
}

// Get survey details
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

$survey = $db->query("
    SELECT s.*, 
           (SELECT COUNT(DISTINCT submitted_at) FROM responses WHERE survey_id = s.id) as total_responses,
           (SELECT COUNT(*) FROM questions WHERE survey_id = s.id) as total_questions
    FROM surveys s 
    WHERE s.id = $survey_id AND s.user_id = $user_id
");

if (!$survey || $survey->num_rows === 0) {
    flash_message("Survey not found", "danger");
    redirect('/dashboard');
}

$survey_data = $survey->fetch_assoc();

require_once '../templates/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1><?php echo htmlspecialchars($survey_data['title']); ?></h1>
        <p class="text-muted"><?php echo htmlspecialchars($survey_data['description']); ?></p>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group">
            <button type="button" class="btn btn-primary" id="export-csv">
                <i class="fas fa-download"></i> Export CSV
            </button>
            <button type="button" class="btn btn-outline-primary" id="print-report">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
</div>

<!-- Response Statistics -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stats-card">
            <div class="card-body">
                <h5 class="card-title">Total Responses</h5>
                <h2 class="card-text"><?php echo $survey_data['total_responses']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card">
            <div class="card-body">
                <h5 class="card-title">Completion Rate</h5>
                <h2 class="card-text">
                    <?php 
                    $completion_rate = $survey_data['total_responses'] > 0 
                        ? round(($survey_data['total_responses'] * 100) / max($survey_data['total_responses'], 1), 1) 
                        : 0;
                    echo $completion_rate . '%';
                    ?>
                </h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card">
            <div class="card-body">
                <h5 class="card-title">Average Time</h5>
                <h2 class="card-text" id="avg-completion-time">Calculating...</h2>
            </div>
        </div>
    </div>
</div>

<!-- Response Analytics -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Response Trend</h5>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary active" data-period="daily">Daily</button>
                    <button type="button" class="btn btn-outline-secondary" data-period="weekly">Weekly</button>
                    <button type="button" class="btn btn-outline-secondary" data-period="monthly">Monthly</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="responseTrendChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Response Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="responseDistributionChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Statistical Analytics & Choice Analysis -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Response Analysis</h5>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs mb-3" id="analysis-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="table-tab" data-bs-toggle="tab" data-bs-target="#table-view" type="button" role="tab">
                    <i class="fas fa-table me-2"></i>Table View
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="chart-tab" data-bs-toggle="tab" data-bs-target="#chart-view" type="button" role="tab">
                    <i class="fas fa-chart-pie me-2"></i>Chart View
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="analysis-tab-content">
            <!-- Table View -->
            <div class="tab-pane fade show active" id="table-view" role="tabpanel">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    This analysis shows key metrics for all question types. Hover over values for additional information.
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" id="unified-analysis-table">
                        <thead class="table-light">
                            <tr>
                                <th>Question</th>
                                <th>Type</th>
                                <th>Responses</th>
                                <th>Most Common Answer</th>
                                <th>Percentage</th>
                                <th>Analysis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be populated via JavaScript -->
                            <tr>
                                <td colspan="6" class="text-center py-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Analyzing responses...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Chart View -->
            <div class="tab-pane fade" id="chart-view" role="tabpanel">
                <div id="chart-analysis-container">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Generating charts...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Response Details Modal -->
<div class="modal fade" id="responseDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Response Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Response details will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const surveyId = <?php echo $survey_id; ?>;
    
    try {
        const response = await fetch(`../api/responses.php?survey_id=${surveyId}`);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Failed to load responses');
        }
        
        console.log('API Response:', data); // Debug log
        
        if (!data || !Array.isArray(data.responses)) {
            throw new Error('Invalid response format');
        }
        
        renderAnalytics(data);
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('question-analysis').innerHTML = `
            <div class="alert alert-danger">
                <strong>Error:</strong> ${error.message}
            </div>
        `;
    }
});

function renderAnalytics(data) {
    const analysisContainer = document.getElementById('question-analysis');
    const tableBody = document.querySelector('#unified-analysis-table tbody');
    
    // Clear both containers
    if (analysisContainer) analysisContainer.innerHTML = '';
    if (tableBody) tableBody.innerHTML = '';
    
    if (!data || !Array.isArray(data.questions) || !Array.isArray(data.responses)) {
        throw new Error('Invalid data format');
    }
    
    // Process each question
    data.questions.forEach((question, index) => {
        // Collect all answers for this question
        const answers = [];
        data.responses.forEach(response => {
            const answer = response.answers.find(a => a.question_id === question.id);
            if (answer && answer.answer !== null && answer.answer !== undefined) {
                answers.push(answer.answer);
            }
        });
        
        const questionData = {
            type: question.type,
            answers: answers
        };
        
        const analysisHtml = createQuestionAnalysis(question, questionData, index);
        analysisContainer.innerHTML += analysisHtml;
    });
    
    // Initialize charts
    data.questions.forEach(question => {
        const chartElement = document.getElementById(`chart-question-${question.id}`);
        if (chartElement) {
            initializeChart(question, data);
        }
    });

    // Calculate average completion time
    let totalTime = 0;
    let validTimeCount = 0;
    if (data.responses.length > 1) {
        for (let i = 1; i < data.responses.length; i++) {
            const current = new Date(data.responses[i].submitted_at);
            const prev = new Date(data.responses[i-1].submitted_at);
            const timeDiff = Math.abs(current - prev);
            if (timeDiff < 3600000) { // Only count if less than 1 hour
                totalTime += timeDiff;
                validTimeCount++;
            }
        }
    }
    const avgTime = validTimeCount > 0 ? Math.round(totalTime / validTimeCount / 1000) : 0;
    document.getElementById('avg-completion-time').textContent = 
        avgTime > 0 ? `${Math.round(avgTime / 60)}m ${avgTime % 60}s` : 'N/A';

    // Render response trends
    renderResponseTrends(data.responses);
    renderResponseDistribution(data.responses);
    renderQuestionCompletion(data);
    
    // Render unified analysis for all question types
    renderChoiceAnalysis(data);
    renderStatisticalAnalysis(data);
}

function renderResponseTrends(responses) {
    const ctx = document.getElementById('responseTrendChart');
    if (!ctx) return;

    // Group responses by date
    const responsesByDate = {};
    responses.forEach(response => {
        const date = new Date(response.submitted_at).toLocaleDateString();
        responsesByDate[date] = (responsesByDate[date] || 0) + 1;
    });

    // Get last 7 days
    const dates = [];
    const counts = [];
    const today = new Date();
    for (let i = 6; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        const dateStr = date.toLocaleDateString();
        dates.push(dateStr);
        counts.push(responsesByDate[dateStr] || 0);
    }

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Responses',
                data: counts,
                borderColor: '#0d6efd',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

function renderResponseDistribution(responses) {
    const ctx = document.getElementById('responseDistributionChart');
    if (!ctx) return;

    // Group responses by hour
    const responsesByHour = new Array(24).fill(0);
    responses.forEach(response => {
        const hour = new Date(response.submitted_at).getHours();
        responsesByHour[hour]++;
    });

    // Create labels for all 24 hours
    const labels = Array.from({length: 24}, (_, i) => 
        `${String(i).padStart(2, '0')}:00`
    );

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Responses',
                data: responsesByHour,
                backgroundColor: '#0d6efd'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

function renderQuestionCompletion(data) {
    const questions = data.questions;
    const responses = data.responses;
    const totalResponses = responses.length;

    // Calculate completion rate for each question
    const completionData = questions.map(question => {
        const answeredCount = responses.reduce((count, response) => {
            const hasAnswer = response.answers.some(answer => 
                answer.question_id === question.id && 
                answer.answer !== null && 
                answer.answer !== undefined
            );
            return count + (hasAnswer ? 1 : 0);
        }, 0);
        
        return {
            question: question.text,
            completion: (answeredCount / totalResponses) * 100
        };
    });

    // Render completion chart
    const ctx = document.getElementById('questionCompletionChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: completionData.map(d => d.question),
            datasets: [{
                label: 'Completion Rate (%)',
                data: completionData.map(d => d.completion),
                backgroundColor: '#198754'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: value => `${value}%`
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: context => `${context.parsed.y.toFixed(1)}%`
                    }
                }
            }
        }
    });
}

// Statistical analysis functions
function calculateStatistics(data) {
    const questions = data.questions;
    const responses = data.responses;
    const stats = [];

    // Process each question that can have numerical analysis (rating questions)
    questions.forEach(question => {
        // Skip non-numeric question types
        if (question.type !== 'rating') {
            return;
        }

        // Collect numerical answers for this question
        const numericAnswers = [];
        responses.forEach(response => {
            const answer = response.answers.find(a => a.question_id === question.id);
            if (answer && answer.answer !== null && answer.answer !== undefined) {
                const value = parseFloat(answer.answer);
                if (!isNaN(value)) {
                    numericAnswers.push(value);
                }
            }
        });

        // Only calculate statistics if we have answers
        if (numericAnswers.length === 0) {
            stats.push({
                question: question.text,
                mean: 'N/A',
                median: 'N/A',
                mode: 'N/A',
                stdDev: 'N/A',
                min: 'N/A',
                max: 'N/A',
                distribution: [0, 0, 0, 0, 0],
                totalResponses: 0
            });
            return;
        }

        // Calculate mean (average)
        const mean = numericAnswers.reduce((sum, val) => sum + val, 0) / numericAnswers.length;

        // Calculate median
        const sortedValues = [...numericAnswers].sort((a, b) => a - b);
        let median;
        const mid = Math.floor(sortedValues.length / 2);
        if (sortedValues.length % 2 === 0) {
            median = (sortedValues[mid - 1] + sortedValues[mid]) / 2;
        } else {
            median = sortedValues[mid];
        }

        // Calculate mode (most common value)
        const valueCounts = {};
        let maxCount = 0;
        let modes = [];

        numericAnswers.forEach(val => {
            valueCounts[val] = (valueCounts[val] || 0) + 1;
            if (valueCounts[val] > maxCount) {
                maxCount = valueCounts[val];
                modes = [val];
            } else if (valueCounts[val] === maxCount) {
                modes.push(val);
            }
        });

        // Calculate standard deviation
        const variance = numericAnswers.reduce((sum, val) => {
            return sum + Math.pow(val - mean, 2);
        }, 0) / numericAnswers.length;
        const stdDev = Math.sqrt(variance);

        // Find min and max
        const min = Math.min(...numericAnswers);
        const max = Math.max(...numericAnswers);

        // Calculate distribution for ratings 1-5
        const distribution = [0, 0, 0, 0, 0];
        numericAnswers.forEach(val => {
            const index = Math.min(Math.max(Math.round(val) - 1, 0), 4);
            distribution[index]++;
        });

        // Format the mode for display
        const formattedMode = modes.length > 3 
            ? 'Multiple values' 
            : modes.map(m => m.toFixed(1)).join(', ');

        stats.push({
            question: question.text,
            mean: mean.toFixed(2),
            median: median.toFixed(2),
            mode: formattedMode,
            stdDev: stdDev.toFixed(2),
            min: min.toFixed(1),
            max: max.toFixed(1),
            distribution: distribution,
            totalResponses: numericAnswers.length
        });
    });

    return stats;
}

function renderStatisticalAnalysis(data) {
    const tableBody = document.querySelector('#unified-analysis-table tbody');
    if (!tableBody) return;

    // Calculate statistics
    const stats = calculateStatistics(data);

    // Clear loading spinner
    tableBody.innerHTML = '';

    // If no stats available, show message
    if (stats.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-3">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No numerical data available for statistical analysis. Add rating questions to see statistics.
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    // Create table rows for each question
    stats.forEach((stat, index) => {
        const row = document.createElement('tr');
        
        // Question column
        const questionCell = document.createElement('td');
        questionCell.textContent = stat.question;
        row.appendChild(questionCell);
        
        // Type column
        const typeCell = document.createElement('td');
        typeCell.textContent = 'Rating';
        row.appendChild(typeCell);
        
        // Responses column
        const responsesCell = document.createElement('td');
        responsesCell.textContent = stat.totalResponses;
        row.appendChild(responsesCell);
        
        // Most Common Answer column
        const mostCommonAnswerCell = document.createElement('td');
        mostCommonAnswerCell.textContent = stat.mode;
        row.appendChild(mostCommonAnswerCell);
        
        // Percentage column
        const percentageCell = document.createElement('td');
        percentageCell.textContent = `${stat.mean} / 5`;
        row.appendChild(percentageCell);
        
        // Analysis column
        const analysisCell = document.createElement('td');
        analysisCell.innerHTML = `
            <span data-bs-toggle="tooltip" title="${stat.question}">
                <span class="badge bg-primary">${stat.mean}</span>
            </span>
        `;
        row.appendChild(analysisCell);
        
        tableBody.appendChild(row);
    });
    
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (typeof bootstrap !== 'undefined') {
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }
}

// Non-numerical response analysis
function analyzeChoiceQuestions(data) {
    const questions = data.questions;
    const responses = data.responses;
    const analysisResults = [];

    // Process each question that is single or multiple choice
    questions.forEach(question => {
        // Only analyze single_choice and multiple_choice questions
        if (question.type !== 'single_choice' && question.type !== 'multiple_choice') {
            return;
        }

        // Get all options for this question to build a reference map
        const optionsMap = {};
        if (question.options && Array.isArray(question.options)) {
            question.options.forEach(option => {
                optionsMap[option.id] = option.text;
            });
        }

        // Count responses for each option
        const optionCounts = {};
        let totalResponses = 0;

        responses.forEach(response => {
            const answers = response.answers.filter(a => a.question_id === question.id);
            answers.forEach(answer => {
                if (answer.answer) {
                    // Sometimes the answer is the ID, sometimes it's the text
                    const optionValue = answer.answer;
                    optionCounts[optionValue] = (optionCounts[optionValue] || 0) + 1;
                    totalResponses++;
                }
            });
        });

        // Skip if no responses
        if (totalResponses === 0) {
            return;
        }

        // Sort options by frequency (descending)
        const sortedOptions = Object.entries(optionCounts)
            .map(([option, count]) => ({
                option: optionsMap[option] || option, // Use option text if available
                count,
                percentage: (count / totalResponses) * 100
            }))
            .sort((a, b) => b.count - a.count);

        // Generate insights based on the data
        let insights = '';
        
        if (sortedOptions.length > 0) {
            const topOption = sortedOptions[0];
            const topPercentage = topOption.percentage.toFixed(1);
            
            insights += `<li><strong>Most popular choice:</strong> "${topOption.option}" was selected ${topOption.count} times (${topPercentage}% of responses).</li>`;
            
            // If we have multiple options, compare top choices
            if (sortedOptions.length > 1) {
                const secondOption = sortedOptions[1];
                const difference = topOption.count - secondOption.count;
                const differencePercentage = ((topOption.count - secondOption.count) / topOption.count * 100).toFixed(1);
                
                if (difference > 0) {
                    insights += `<li><strong>Preference margin:</strong> "${topOption.option}" was chosen ${difference} more times than "${secondOption.option}" (${differencePercentage}% more frequently).</li>`;
                } else if (difference === 0) {
                    insights += `<li><strong>Tied preference:</strong> "${topOption.option}" and "${secondOption.option}" were equally popular with ${topOption.count} selections each.</li>`;
                }
            }
            
            // Check if there's a strong consensus or split opinion
            if (topOption.percentage > 70) {
                insights += `<li><strong>Strong consensus:</strong> A clear majority of ${topPercentage}% selected "${topOption.option}."</li>`;
            } else if (sortedOptions.length > 1 && (sortedOptions[0].percentage - sortedOptions[1].percentage) < 10) {
                insights += `<li><strong>Split opinion:</strong> There's no clear consensus between the top choices.</li>`;
            }
            
            // Check for unpopular options
            const unpopularOptions = sortedOptions.filter(option => option.percentage < 10);
            if (unpopularOptions.length > 0 && sortedOptions.length > unpopularOptions.length) {
                insights += `<li><strong>Unpopular choices:</strong> ${unpopularOptions.length} option(s) were selected by less than 10% of respondents.</li>`;
            }
        }

        // Add to analysis results
        analysisResults.push({
            question: question.text,
            type: question.type,
            totalResponses,
            options: sortedOptions,
            insights
        });
    });

    return analysisResults;
}

function renderChoiceAnalysis(data) {
    const tableBody = document.querySelector('#unified-analysis-table tbody');
    const chartContainer = document.getElementById('chart-analysis-container');
    if (!tableBody || !chartContainer) return;

    // Analyze choice questions
    const analysisResults = analyzeChoiceQuestions(data);

    // If no results and the table is empty, show message
    if (analysisResults.length === 0 && tableBody.innerHTML.trim() === '') {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-3">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No analyzable questions found in this survey.
                    </div>
                </td>
            </tr>
        `;
        
        chartContainer.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No analyzable questions found in this survey.
            </div>
        `;
        return;
    }

    // Clear chart container
    chartContainer.innerHTML = '';

    // Create analysis entries for each question
    analysisResults.forEach((result, index) => {
        // Add to table
        const row = document.createElement('tr');
        
        // Question column
        const questionCell = document.createElement('td');
        questionCell.textContent = result.question;
        row.appendChild(questionCell);
        
        // Type column
        const typeCell = document.createElement('td');
        typeCell.textContent = result.type === 'single_choice' ? 'Single Choice' : 'Multiple Choice';
        row.appendChild(typeCell);
        
        // Responses column
        const responsesCell = document.createElement('td');
        responsesCell.textContent = result.totalResponses;
        row.appendChild(responsesCell);
        
        // Most Common Answer column
        const mostCommonAnswerCell = document.createElement('td');
        if (result.options.length > 0) {
            mostCommonAnswerCell.textContent = result.options[0].option;
        } else {
            mostCommonAnswerCell.textContent = 'N/A';
        }
        row.appendChild(mostCommonAnswerCell);
        
        // Percentage column
        const percentageCell = document.createElement('td');
        if (result.options.length > 0) {
            percentageCell.innerHTML = `<span class="badge bg-primary">${result.options[0].percentage.toFixed(1)}%</span>`;
        } else {
            percentageCell.textContent = 'N/A';
        }
        row.appendChild(percentageCell);
        
        // Analysis column - simplified insights
        const analysisCell = document.createElement('td');
        let analysisText = '';
        
        if (result.options.length > 1) {
            const topOption = result.options[0];
            const secondOption = result.options[1];
            const difference = topOption.count - secondOption.count;
            
            if (topOption.percentage > 70) {
                analysisText = `Strong consensus`;
            } else if (Math.abs(topOption.percentage - secondOption.percentage) < 10) {
                analysisText = `Split opinion`;
            } else {
                analysisText = `Clear preference`;
            }
        } else if (result.options.length === 1) {
            analysisText = `Unanimous choice`;
        }
        
        analysisCell.innerHTML = `
            <span data-bs-toggle="tooltip" title="${result.insights.replace(/(<([^>]+)>)/gi, '')}">
                ${analysisText} <i class="fas fa-info-circle text-muted"></i>
            </span>
        `;
        row.appendChild(analysisCell);
        
        tableBody.appendChild(row);
        
        // Add to chart view
        const chartCard = document.createElement('div');
        chartCard.className = 'card mb-4';
        
        // Generate the chart ID
        const chartId = `choice-chart-${index}`;
        
        chartCard.innerHTML = `
            <div class="card-header bg-light">
                <h6 class="mb-0">${result.question}</h6>
                <small class="text-muted">${result.type === 'single_choice' ? 'Single Choice' : 'Multiple Choice'} · ${result.totalResponses} responses</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <canvas id="${chartId}" height="250"></canvas>
                    </div>
                    <div class="col-md-4">
                        <h6 class="mb-3">Key Insights:</h6>
                        <ul class="insights-list">
                            ${result.insights || '<li>No significant insights available.</li>'}
                        </ul>
                    </div>
                </div>
            </div>
        `;
        
        chartContainer.appendChild(chartCard);
        
        // Initialize chart
        setTimeout(() => {
            const ctx = document.getElementById(chartId);
            if (ctx) {
                // Prepare chart data
                const labels = result.options.map(o => o.option);
                const data = result.options.map(o => o.count);
                
                // Create a pie chart for choice distribution
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: [
                                '#0d6efd', '#6f42c1', '#d63384', '#fd7e14', '#198754',
                                '#20c997', '#0dcaf0', '#6610f2', '#dc3545', '#ffc107'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 15
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        const percentage = ((value / result.totalResponses) * 100).toFixed(1);
                                        return `${context.label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }, 0);
    });
    
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (typeof bootstrap !== 'undefined') {
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }
}

function initializeChart(question, data) {
    const chartElement = document.getElementById(`chart-question-${question.id}`);
    if (!chartElement) return;
    
    const answers = data.responses.reduce((acc, response) => {
        const answer = response.answers.find(a => a.question_id === question.id);
        if (answer && answer.answer !== null && answer.answer !== undefined) {
            acc.push(answer.answer);
        }
        return acc;
    }, []);
    
    switch (question.type) {
        case 'multiple_choice':
        case 'single_choice':
            const optionCounts = answers.reduce((counts, answer) => {
                counts[answer] = (counts[answer] || 0) + 1;
                return counts;
            }, {});
            
            new Chart(chartElement, {
                type: 'pie',
                data: {
                    labels: Object.keys(optionCounts),
                    datasets: [{
                        data: Object.values(optionCounts),
                        backgroundColor: [
                            '#0d6efd',
                            '#6610f2',
                            '#6f42c1',
                            '#d63384',
                            '#dc3545'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            break;
            
        case 'rating':
            // Convert answers to numbers and filter out any non-numeric values
            const ratings = data.answers.map(answer => parseFloat(answer)).filter(rating => !isNaN(rating));
            
            // Calculate average only if there are valid ratings
            const average = ratings.length > 0 ? ratings.reduce((a, b) => a + b, 0) / ratings.length : 0;
            
            analysisContent = `
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="mb-3">Average Rating: ${average.toFixed(1)}</h4>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-success" 
                                 style="width: ${(average / 5) * 100}%">
                                ${average.toFixed(1)} / 5
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <canvas id="chart-q${index}" height="200"></canvas>
                    </div>
                </div>
            `;
            
            // Initialize rating distribution chart
            setTimeout(() => {
                const ctx = document.getElementById(`chart-q${index}`);
                const ratingCounts = [1,2,3,4,5].map(rating => 
                    ratings.filter(r => Math.round(r) === rating).length
                );
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                        datasets: [{
                            label: 'Ratings',
                            data: ratingCounts,
                            backgroundColor: '#ffc107'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }, 0);
            break;
    }
}

function createQuestionAnalysis(question, data, index) {
    const totalResponses = data.answers.length;
    let analysisContent = '';
    
    switch (data.type) {
        case 'multiple_choice':
        case 'single_choice':
            const optionCounts = {};
            data.answers.forEach(answer => {
                if (!optionCounts[answer]) {
                    optionCounts[answer] = 0;
                }
                optionCounts[answer]++;
            });
            
            analysisContent = `
                <div class="row">
                    <div class="col-md-8">
                        <canvas id="chart-q${index}" height="200"></canvas>
                    </div>
                    <div class="col-md-4">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Option</th>
                                        <th>Count</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${Object.entries(optionCounts).map(([option, count]) => `
                                        <tr>
                                            <td>${option}</td>
                                            <td>${count}</td>
                                            <td>${Math.round((count / totalResponses) * 100)}%</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            // Initialize chart after DOM update
            setTimeout(() => {
                const ctx = document.getElementById(`chart-q${index}`);
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: Object.keys(optionCounts),
                        datasets: [{
                            data: Object.values(optionCounts),
                            backgroundColor: [
                                '#007bff', '#28a745', '#dc3545', '#ffc107', 
                                '#17a2b8', '#6610f2', '#fd7e14', '#20c997'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }, 0);
            break;
            
        case 'text':
            analysisContent = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Response</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.answers.map(answer => `
                                <tr>
                                    <td>${answer}</td>
                                    <td>${new Date().toLocaleDateString()}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            break;
            
        case 'rating':
            // Convert answers to numbers and filter out any non-numeric values
            const ratings = data.answers.map(answer => parseFloat(answer)).filter(rating => !isNaN(rating));
            
            // Calculate average only if there are valid ratings
            const average = ratings.length > 0 ? ratings.reduce((a, b) => a + b, 0) / ratings.length : 0;
            
            analysisContent = `
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="mb-3">Average Rating: ${average.toFixed(1)}</h4>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-success" 
                                 style="width: ${(average / 5) * 100}%">
                                ${average.toFixed(1)} / 5
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <canvas id="chart-q${index}" height="200"></canvas>
                    </div>
                </div>
            `;
            
            // Initialize rating distribution chart
            setTimeout(() => {
                const ctx = document.getElementById(`chart-q${index}`);
                const ratingCounts = [1,2,3,4,5].map(rating => 
                    ratings.filter(r => Math.round(r) === rating).length
                );
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                        datasets: [{
                            label: 'Ratings',
                            data: ratingCounts,
                            backgroundColor: '#ffc107'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }, 0);
            break;
    }
    
    return `
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button ${index === 0 ? '' : 'collapsed'}" 
                        type="button" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#collapse${index}">
                    ${question.text}
                </button>
            </h2>
            <div id="collapse${index}" 
                 class="accordion-collapse collapse ${index === 0 ? 'show' : ''}"
                 data-bs-parent="#question-analysis">
                <div class="accordion-body">
                    ${analysisContent}
                </div>
            </div>
        </div>
    `;
}

// Export to CSV
function convertToCSV(responses) {
    if (responses.length === 0) return '';
    
    // Get all unique questions
    const questions = [...new Set(
        responses.flatMap(r => r.answers.map(a => a.question))
    )];
    
    // Create CSV header
    const header = ['Submission Date', ...questions].join(',') + '\n';
    
    // Create CSV rows
    const rows = responses.map(response => {
        const answers = {};
        response.answers.forEach(a => answers[a.question] = a.answer);
        
        return [
            response.submitted_at,
            ...questions.map(q => `"${answers[q] || ''}"`)
        ].join(',');
    });
    
    return header + rows.join('\n');
}

// Export to CSV
document.getElementById('export-csv').addEventListener('click', async () => {
    try {
        // Show loading state
        const exportBtn = document.getElementById('export-csv');
        const originalText = exportBtn.innerHTML;
        exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
        exportBtn.disabled = true;

        // Fetch the CSV file
        const response = await fetch(`../api/export.php?survey_id=<?php echo $survey_id; ?>`);
        
        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Failed to export responses');
        }

        // Get the filename from the Content-Disposition header
        const contentDisposition = response.headers.get('Content-Disposition');
        let filename = 'survey_responses.csv';
        if (contentDisposition) {
            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(contentDisposition);
            if (matches != null && matches[1]) {
                filename = matches[1].replace(/['"]/g, '');
            }
        }

        // Create blob and download
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        // Restore button state
        exportBtn.innerHTML = originalText;
        exportBtn.disabled = false;

    } catch (error) {
        console.error('Export error:', error);
        alert('Failed to export responses: ' + error.message);
        
        // Restore button state
        const exportBtn = document.getElementById('export-csv');
        exportBtn.innerHTML = '<i class="fas fa-download"></i> Export CSV';
        exportBtn.disabled = false;
    }
});

// Print report
document.getElementById('print-report').addEventListener('click', () => {
    window.print();
});
</script>

<?php require_once '../templates/footer.php'; ?>


