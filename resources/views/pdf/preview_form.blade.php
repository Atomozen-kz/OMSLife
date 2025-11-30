<div class="d-flex row">
    <!-- HTML Preview -->
    <div id="pdf-preview" class="col-7 border p-4 bg-white">
        @include('pdf.spravka_html_pdf')
    </div>

    <!-- Input Form -->
    <div class=" col-5 p-4">
        <form id="preview-form"  action="{{ route('generate.pdf') }}" method="POST"  data-controller="" data-action="" novalidate>
            @csrf
            <div class="form-group">
                <label for="last_name">Фамилия</label>
                <input readonly type="text" id="sotrudnik.last_name" name="last_name" class="form-control" value="{{$sotrudnik->last_name}}" oninput="updatePreview()">
            </div>

            <div class="form-group">
                <label for="first_name">Имя</label>
                <input readonly type="text" id="sotrudnik.first_name" name="first_name" class="form-control" value="{{$sotrudnik->first_name}}" oninput="updatePreview()" >
            </div>

            <div class="form-group">
                <label for="father_name">Отчество</label>
                <input readonly type="text" id="sotrudnik.father_name" name="father_name" class="form-control" value="{{$sotrudnik->father_name}}" oninput="updatePreview()">
            </div>

            <div class="row">
                <div class=" col-6 form-group">
                    <label for="father_name">Лауазымы</label>
                    <textarea type="text" id="sotrudnik.doljnost_kz" name="doljnost_kz" class="form-control" oninput="updatePreview()">{{mb_convert_case($sotrudnik->position->name_kz, MB_CASE_LOWER, "UTF-8")}}</textarea>
                </div>
                <div class=" col-6 form-group">
                    <label for="father_name">Должность</label>
                    <textarea type="text" id="sotrudnik.doljnost_ru" name="doljnost_ru" class="form-control" oninput="updatePreview()">{{mb_convert_case($sotrudnik->position->name_ru, MB_CASE_LOWER, "UTF-8")}}</textarea>
                </div>
            </div>

            <div class="row">
                <div class=" col-6 form-group">
                    <label for="father_name">Структура (каз)</label>
                    <textarea type="text" id="sotrudnik.organization_kz" name="organization_kz" class="form-control" oninput="updatePreview()">{{mb_convert_case($sotrudnik->organization->name_kz, MB_CASE_LOWER, "UTF-8")}}</textarea>
                </div>
                <div class=" col-6 form-group">
                    <label for="father_name">Структура (рус)</label>
                    <textarea type="text" id="sotrudnik.organization_ru" name="organization_ru" class="form-control" oninput="updatePreview()">{{mb_convert_case($sotrudnik->organization->name_ru, MB_CASE_LOWER, "UTF-8")}}</textarea>
                </div>
            </div>

                <div class="form-group">
                    <label for="signer">Подписант (должность)</label>
                    <textarea type="text" id="signer.position" name="signer_position" class="form-control"  oninput="updatePreview()">{{$spravka->signer->position}}</textarea>
                </div>

                <div class="form-group">
                    <label for="signer">Подписант (ФИО)</label>
                    <input readonly type="text" id="signer.fio" name="signer_fio" class="form-control"  oninput="updatePreview()" value="{{$spravka->signer->last_name . ' ' . mb_substr($spravka->signer->first_name, 0, 1) . '. ' . mb_substr($spravka->signer->father_name, 0, 1) . '.'}}">
                </div>

                <input type="hidden" id="text_kz_to_pdf" name="text_kz_to_pdf">
                <input type="hidden" id="text_ru_to_pdf" name="text_ru_to_pdf">
                <input type="hidden" id="id_spravka" name="id_spravka" value="{{$spravka->id}}">
                <button type="submit" class="btn btn-primary btn-xl" id="generate-pdf-button">Генерировать PDF</button>
        </form>
    </div>
</div>

<script>
    var route_generate = '{{ route('generate.pdf') }}';
    updatePreview()
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('post-form'); // ID формы, который автоматически добавляет Orchid
        if (form) {
            form.action = "{{ route('generate.pdf') }}";
        }
        console.log('Form loaded');

    });

    window.addEventListener("beforeunload", function(event) {
        console.log("UNLOAD:1");
        window.onbeforeunload = null;
        event.preventDefault();
        event.returnValue = null; //"Any text"; //true; //false;
        return null; //"Any text"; //true; //false;

    });

    function updatePreview() {
        const form = document.getElementById('post-form'); // ID формы, который автоматически добавляет Orchid
        if (form) {
            form.action = "{{ route('generate.pdf') }}";
            console.log('Form updated');
        }

        const lastName = document.getElementById('sotrudnik.last_name').value;
        const firstName = document.getElementById('sotrudnik.first_name').value;
        const fatherName = document.getElementById('sotrudnik.father_name').value;
        const doljnostKz = document.getElementById('sotrudnik.doljnost_kz').value;
        const doljnostRu = document.getElementById('sotrudnik.doljnost_ru').value;
        const organizationKz = document.getElementById('sotrudnik.organization_kz').value;
        const organizationRu = document.getElementById('sotrudnik.organization_ru').value;

        document.getElementById('text_kz_to_pdf').value = '"Өзенмұнайгаз" акционерлік қоғамының '
            +organizationKz+' '+doljnostKz+ ' лауазымында жұмыс жасайды.';

        document.getElementById('text_ru_to_pdf').value = 'работает в должности '
            +doljnostRu+' '+organizationRu+ ' акционерного общества "Өзенмұнайгаз".'

        const textKz = '"Өзенмұнайгаз" акционерлік қоғамының ' +
            '<span class="text-info text-u-l">'+organizationKz+'</span> ' +
            '<span class="text-success text-u-l">'+doljnostKz+' </span> ' +
            'лауазымында жұмыс жасайды.';

        const textRu = 'работает в должности ' +
            '<span class="text-success text-u-l">'+doljnostRu+' </span>' +
            '<span class="text-info text-u-l">'+organizationRu+' </span>' +
            'акционерного общества "Озенмунайгаз".';

        const signer_fio = document.getElementById('signer.fio').value;
        const signer_position = document.getElementById('signer.position').value;

        // Обновляем тексты
        document.getElementById('text_kz').innerHTML = textKz;
        document.getElementById('text_ru').innerHTML = textRu;
        document.getElementById('fio_kz').innerHTML = lastName+' '+firstName+' '+fatherName;
        document.getElementById('fio_ru').innerHTML = lastName+' '+firstName+' '+fatherName;
        document.getElementById('signer_position').innerHTML = signer_position;
        document.getElementById('signer_fio').innerHTML = signer_fio;

        // document.getElementById('preview-form').querySelector('input[name="signer"]').value = signer;
    }

    // document.getElementById('generate-pdf-button').addEventListener('click', function () {
    //     window.onbeforeunload = null;
    //     window.confirm = null
    //     // const formData = new FormData(document.getElementById('post-form'));
    //
    //     // Сбор данных формы
    //     const formData = new FormData();
    //     formData.append('last_name', document.getElementById('sotrudnik.last_name').value);
    //     formData.append('first_name', document.getElementById('sotrudnik.first_name').value);
    //     formData.append('father_name', document.getElementById('sotrudnik.father_name').value);
    //     formData.append('doljnost_kz', document.getElementById('sotrudnik.doljnost_kz').value);
    //     formData.append('doljnost_ru', document.getElementById('sotrudnik.doljnost_ru').value);
    //     formData.append('organization_kz', document.getElementById('sotrudnik.organization_kz').value);
    //     formData.append('organization_ru', document.getElementById('sotrudnik.organization_ru').value);
    //     formData.append('signer_position', document.getElementById('signer.position').value);
    //     formData.append('signer_fio', document.getElementById('signer.fio').value);
    //     formData.append('text_kz_to_pdf', document.getElementById('text_kz_to_pdf').value);
    //     formData.append('text_ru_to_pdf', document.getElementById('text_ru_to_pdf').value);
    //     formData.append('id_spravka', document.getElementById('id_spravka').value);
    //     formData.append('_token', document.querySelector('input[name="_token"]').getAttribute('value'));
    //
    //     // Отправка данных на сервер
    //     fetch('/admin/generate-pdf', {
    //         method: 'POST',
    //         body: formData,
    //         // headers: {
    //         //     'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').getAttribute('value')
    //         // }
    //     })
    //         .then(response => response.json())
    //         .then(data => {
    //             if (data.success) {
    //                 // Действия при успешном выполнении
    //                 // alert('PDF успешно сгенерирован!');
    //
    //                 window.location.href = '/admin/pdf-view/'+data.spravka;
    //             } else {
    //                 // Обработка ошибок
    //                 alert('Произошла ошибка при генерации PDF.');
    //             }
    //         })
    //         .catch(error => {
    //             console.error('Ошибка:', error);
    //             alert('Произошла ошибка при отправке данных.');
    //         });
    // });
</script>
