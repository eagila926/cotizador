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
                
        $.each(this.items.activos, function (pos, dict) {
            console.log('Posisicion',pos);
            console.log('Diccionario',dict);
            dict.pos = pos;

            if(dict.unidad === 'mg' || dict.unidad === 'UI'){
                dict.cant = dict.cant/1000;
                dict.masa_cap = dict.cant * parseFloat(dict.factor);
            }else if(dict.unidad_compra === 'mcg'){
                dict.cant = dict.cant/1000000;
            }else{
                dict.cant = dict.cant;
            }

            
            

            
        });

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
                {"data": "unidad"},
                {"data": "masa_cap"},
                {"data": "masa_final"},
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
                        return row.cant;
                    }
                },
                {
                    targets: [4],
                    class: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        return row.unidad;
                    }
                },
                {
                    targets: [5],
                    class: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        return row.masa_cap+' g';
                    }
                },
                {
                    targets: [6],
                    class: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        return row.masa_final+' g';
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

            console.clear();
            
            ui.item.cant = 0;           
            ui.item.masa_cap = 0;           
            ui.item.masa_final = 0;
            ui.item.unidad = "g";

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
                    //$('td:eq(5)', tblformu.row(tr.row).node()).html('$' + formus.items.activos[tr.row].subtotal.toFixed(2));
                })
                .on('change', 'select[name="unit"]', function () {
                    console.clear();
                    var unidad = String($(this).val());
                    var tr = tblformu.cell($(this).closest('td, li')).index();
                    formus.items.activos[tr.row].unidad = unidad;
                    formus.calculate_invoice();
                    
                });
                $('.btnAddActivo').on('click', function () {
                    event.preventDefault();
                    formus.calculate_invoice();
                    console.log(formus.items);
                    formus.add(ui.item);
                    $(this).val('');
                });
            
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
            //$('td:eq(5)', tblformu.row(tr.row).node()).html('$' + formus.items.activos[tr.row].subtotal.toFixed(2));
        })
        .on('change', 'select[name="unit"]', function () {
            console.clear();
            var unidad = String($(this).val());
            var tr = tblformu.cell($(this).closest('td, li')).index();
            formus.items.activos[tr.row].unidad = unidad;
            formus.calculate_invoice();
            
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
    
    formus.list();
});