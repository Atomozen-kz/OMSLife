<div class="bg-body-tertiary rounded p-4 mb-3">
    <h5 class="mb-3">📝 Детали заявления</h5>
    <iframe style="width: 100%; height:1080px;" src="{{\Illuminate\Support\Facades\Storage::url($request->pdf_path)}}"></iframe>
{{--    @if(!empty($request->form_data))--}}
{{--        <div class="row">--}}
{{--            @foreach($request->form_data as $fieldName => $fieldValue)--}}
{{--                <div class="col-md-6 mb-3">--}}
{{--                    <div class="card border-0 bg-white">--}}
{{--                        <div class="card-body">--}}
{{--                            <h6 class="card-title text-primary">{{ $fieldName }}</h6>--}}
{{--                            <p class="card-text">--}}
{{--                                @if(is_array($fieldValue))--}}
{{--                                    {{ implode(', ', $fieldValue) }}--}}
{{--                                @else--}}
{{--                                    {{ $fieldValue }}--}}
{{--                                @endif--}}
{{--                            </p>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            @endforeach--}}
{{--        </div>--}}
{{--    @else--}}
{{--        <div class="text-center text-muted py-4">--}}
{{--            <i class="icon-docs" style="font-size: 48px; opacity: 0.3;"></i>--}}
{{--            <p class="mt-3">Дополнительные данные не заполнены</p>--}}
{{--        </div>--}}
{{--    @endif--}}

{{--    <div class="mt-4 p-3 bg-light rounded">--}}
{{--        <h6 class="mb-3">🔍 Предпросмотр документа</h6>--}}
{{--        <div class="d-flex gap-2">--}}
{{--            <a href="{{ route('platform.financial-assistance.request.html', $request->id) }}"--}}
{{--               target="_blank"--}}
{{--               class="btn btn-outline-primary btn-sm">--}}
{{--                <i class="icon-eye"></i> Открыть HTML превью--}}
{{--            </a>--}}

{{--            <button class="btn btn-outline-secondary btn-sm" onclick="generatePDF({{ $request->id }})">--}}
{{--                <i class="icon-doc"></i> --}}
{{--            </button>--}}
{{--        </div>--}}
{{--    </div>--}}
</div>



<script>
function generatePDF(requestId) {
    alert('Функция генерации PDF будет реализована позже');
}
</script>
