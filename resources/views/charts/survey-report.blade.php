<div class="row">
    <div class="col-md-6">
        <h3>Распределение вариантов ответов</h3>
        <canvas id="optionsChart"></canvas>
    </div>
    <div class="col-md-6">
        <h3>Текстовые ответы</h3>
        <ul>
            @foreach($responses as $response)
                @foreach($response->answers as $answer)
                    @if($answer->user_text_response)
                        <li>{{ $answer->user_text_response }}</li>
                    @endif
                @endforeach
            @endforeach
        </ul>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.js" integrity="sha512-7DgGWBKHddtgZ9Cgu8aGfJXvgcVv4SWSESomRtghob4k4orCBUTSRQ4s5SaC2Rz+OptMqNk0aHHsaUBk6fzIXw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>


<script>
    const optionsData = {
        labels: @json($survey->questions->pluck('question_text')),
        datasets: [{
            data: @json($survey->questions->map(fn($q) => $q->answers->count())),
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56'],
        }]
    };

    const optionsChart = new Chart(document.getElementById('optionsChart'), {
        type: 'bar',
        data: optionsData,
    });
</script>
