$(document).ready(function() {
    // Initialize an empty array to store the data
    var dataArray = [];

    // Initialize variables for codigoOdoo and activo
    var codigoOdoo = 0;
    var activo = "";

    // Initialize the DataTable
    var table = $("#tblformu").DataTable({
        responsive:true,
        columns: [
            { data: null, defaultContent: '<button type="button" class="btn btn-danger btn-flat btnRemoveRow"><i class="fas fa-trash"></i></button>' },
            { data: 'codigoOdoo' },
            { data: 'activo' },
            { data: 'cantidad' },
            { data: 'unidad' }
        ]
    });

    // Function to add a new row to the table
    function addRowToTable(codigoOdoo, activo) {
        var cantidad = $("input[name='cant']").val();
        var unidad = $("select[name='unidad']").val();
        var codigoOdooInt = parseInt(codigoOdoo, 10);

        // Push the data into the dataArray
        dataArray.push({
            codigoOdoo: codigoOdooInt,
            activo: activo,
            cantidad: cantidad,
            unidad: unidad
        });

        // Clear the input fields
        $("input[name='search']").val("").focus();
        $("input[name='cant']").val("");
        $("select[name='unidad']").val("");

        // Update the DataTable
        table.clear().rows.add(dataArray).draw();
    }

    // Add an event listener to the "Añadir Activo" button
    $(".btnAddActivo").on("click", function() {
        addRowToTable(codigoOdoo, activo);
        console.log(dataArray);
    });

    // Add an event listener to remove rows
    $('#tblformu tbody').on('click', '.btnRemoveRow', function () {
        var data = table.row($(this).parents('tr')).data();
        var index = dataArray.findIndex(function (item) {
            return item.codigoOdoo === data.codigoOdoo;
        });
        if (index !== -1) {
            dataArray.splice(index, 1);
            table.row($(this).parents('tr')).remove().draw();
        }
    });

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
                codigoOdoo = ui.item.cod_inven; 
                activo = ui.item.descripcion;         

            }
        });
    });

    //guardar cotizacion

    $(".btnCotizar").on("click", function() {
        // Send the dataArray to the Django view via AJAX
        $.ajax({
            url: 'activosformulas/add/', // URL of your Django view
            type: 'POST',
            data: JSON.stringify(dataArray), // Send the dataArray as JSON
            contentType: 'application/json',
            success: function(response) {
                console.log(response.message);
                // Handle success, e.g., show a success message to the user
            },
            error: function(error) {
                console.error('Error:', error);
                // Handle error, e.g., show an error message to the user
            }
        });
    });
});
