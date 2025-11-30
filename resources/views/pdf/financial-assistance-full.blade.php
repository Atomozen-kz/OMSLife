{{-- 
    Пример полного документа материальной помощи
    Использует header, центральную часть из БД и footer
--}}

@php
    // Пример данных - в реальном использовании эти данные будут переданы из контроллера
    $headerData = [
        'sotrudnik' => $sotrudnik ?? null,
        'assistance_type' => $assistanceType ?? null,
        'current_date' => $currentDate ?? date('d.m.Y'),
        'department' => $sotrudnik->organization->name_ru ?? null,
        'request_id' => $request->id ?? null,
    ];

    $footerData = [
        'sotrudnik' => $sotrudnik ?? null,
        'signer' => $signer ?? null,
        'current_date' => $currentDate ?? date('d.m.Y'),
        'processed_date' => $request->processed_at ? $request->processed_at->format('d.m.Y') : null,
        'request_id' => $request->id ?? null,
    ];
@endphp

{{-- Header документа --}}
@include('pdf.financial-assistance-header', $headerData)

{{-- Центральная часть из базы данных --}}
<div class="document-content">
    {!! $contentHtml ?? '<p>Содержимое не найдено</p>' !!}
</div>

{{-- Footer документа --}}
@include('pdf.financial-assistance-footer', $footerData)
