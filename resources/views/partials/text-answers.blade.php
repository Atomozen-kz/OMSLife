<div class="bg-white rounded shadow-sm mb-3 pt-3">
    <div class="d-flex px-3 align-items-center">
        <div class="text-body-emphasis px-2 mt-2 mb-0">
            <div class="d-flex align-items-center">
                <h7>{{$question}}</h7>
            </div>
        </div>
    </div>
    <div style="padding-left: 3rem; padding-bottom: 3rem;">
        <table class="table">
            @foreach($responses as $key => $response)
                <tr>
                    <td style="min-width: 75px;">{{ $key + 1 }}</td>
                    <td style="min-width: 210px;">{{$response['sotrudnik']}}</td>
                    <td>{{ $response['text'] }}</td>
                </tr>
            @endforeach
        </table>
    </div>


</div>
