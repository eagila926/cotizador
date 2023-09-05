var tblformu;
var formus = {
    items: {
        cod_formula: '',
        nombre: '',
        subtotal: 0.00,
        dosis: 0,
        activos: []
    },
    calculate_invoice: function () {
        var mfinal = 0.00;
        var vfinal = 0.00;
        

        
        $.each(this.items.activos, function (pos, dict) {
            dict.pos = pos;
            if(dict.unidad_compra === 'mg' || dict.unidad_compra === 'UI'){
                dict.cant = dict.cant/1000
            }else if(dict.unidad_compra === 'mcg'){
                dict.cant = dict.cant/1000000
            }else{
                dict.cant = dict.cant
            }
            //quedaste aqui
            mfinal = dict.cant * parseFloat(dict.factor);

            
        });

        console.log('masa final',dict.mfinal)
        this.items.subtotal = subtotal;
        this.items.iva = this.items.subtotal * iva;
        this.items.total = this.items.subtotal + this.items.iva;


        $('input[name="subtotal"]').val(this.items.subtotal.toFixed(2));
        $('input[name="ivacalc"]').val(this.items.iva.toFixed(2));
        $('input[name="total"]').val(this.items.total.toFixed(2));
    },
    add: function (item) {
        this.items.activos.push(item);
        this.list();
    },
    list: function () {

        tblformu = $('#tblformu').DataTable({
            responsive: true,
            autoWidth: false,
            destroy: true,
            data: this.items.activos,
            columns: [
                {"data": "cod_inven"},
                {"data": "descripcion"},
                {"data": "factor"},
                {"data": "cant"},
                {"data": "unidad_compra"},
                {"data": "valor_venta"},
                {"data": "valor_costo"},
            ],
            columnDefs: [
                {
                    targets: [0],
                    class: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        return '<a rel="remove" class="btn btn-danger btn-xs btn-flat" style="color: white;"><i class="fas fa-trash-alt"></i></a>';
                    }
                },
                {
                    targets: [3],
                    class: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        return '<input type="text" name="cant" class="form-control form-control-sm input-sm" autocomplete="off" value="' + row.cant + '">';
                    }
                },
                {
                    targets: [4],
                    class: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        return '<select id="unidad_compra" class="form-control form-control-sm select-sm" autocomplete="off" > <option value="g"> g </option> <option value="mg"> mg </option> <option value="mcg"> mcg </option> <option value="UI"> UI </option>';
                    }
                },
                {
                    targets: [5],
                    class: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        return '$' + parseFloat(data).toFixed(2);
                    }
                },
                {
                    targets: [6],
                    class: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        return '$' + parseFloat(data).toFixed(2);
                    }
                },
                
            ],
            rowCallback(row, data, displayNum, displayIndex, dataIndex) {

                $(row).find('input[name="cant"]').TouchSpin({
                    min: 1,
                    max: 1000000000,
                    step: 1
                });

            },
            initComplete: function (settings, json) {

            }
        });
    },
};

$(function () {
    
    $('.select2').select2({
        theme: "bootstrap4",
        language: 'es'
    });

    // search activos

    $('input[name="search"]').autocomplete({
        source: function (request, response) {
            $.ajax({
                url: window.location.pathname,
                type: 'POST',
                data: {
                    'action': 'search_activos',
                    'term': request.term
                },
                dataType: 'json',
            }).done(function (data) {
                response(data);
                console.log(data);
            }).fail(function (jqXHR, textStatus, errorThrown) {
                //alert(textStatus + ': ' + errorThrown);
            }).always(function (data) {

            });
        },
        delay: 500,
        minLength: 1,
        select: function (event, ui) {
            event.preventDefault();
            console.clear();            
            console.log(formus.items);
            formus.add(ui.item);
            $(this).val('');
        }
    });

    $('.btnRemoveAll').on('click', function () {
        if (formus.items.activos.length === 0) return false;
        alert_action('Notificación', '¿Estas seguro de eliminar todos los items de tu detalle?', function () {
            formus.items.activos = [];
            formus.list();
        });
    });

    // event cant
    $('#tblformu tbody')
        .on('click', 'a[rel="remove"]', function () {
            var tr = tblformu.cell($(this).closest('td, li')).index();
            alert_action('Notificación', '¿Estas seguro de eliminar el producto de tu detalle?', function () {
                formus.items.activos.splice(tr.row, 1);
                formus.list();
            });
        })
        .on('change', 'input[name="cant"]', function () {
            console.clear();
            var cant = parseInt($(this).val());
            var tr = tblformu.cell($(this).closest('td, li')).index();
            formus.items.activos[tr.row].cant = cant;
            formus.calculate_invoice();
            $('td:eq(5)', tblformu.row(tr.row).node()).html('$' + formus.items.activos[tr.row].subtotal.toFixed(2));
        });

    $('.btnClearSearch').on('click', function () {
        $('input[name="search"]').val('').focus();
    });

    // event submit
    $('form').on('submit', function (e) {
        e.preventDefault();

        if(formus.items.activos.length === 0){
            message_error('Debe al menos tener un item en su detalle de venta');
            return false;
        }

        formus.items.date_joined = $('input[name="date_joined"]').val();
        formus.items.cli = $('select[name="cli"]').val();
        var parameters = new FormData();
        parameters.append('action', $('input[name="action"]').val());
        parameters.append('formus', JSON.stringify(formus.items));
        submit_with_ajax(window.location.pathname, 'Notificación', '¿Estas seguro de realizar la siguiente acción?', parameters, function () {
            location.href = '/erp/sale/list/';
        });
    });
    
    // Esto se puso aqui para que funcione bien el editar y calcule bien los valores del iva. // sino tomaría el valor del iva de la base debe
    // coger el que pusimos al inicializarlo. 
    formus.list();
});