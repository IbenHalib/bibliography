function a_bind() {

    $('.edit_template').off();
    $('.edit_template').on('click', function () {
        $('#container').load(this.href, function () {
            a_bind();
        });
        return false;
    })

    $('.rm_template').off();

    $('.rm_template').on('click', function () {

        var url = this.href;

        $('.ui-dialog:has(#' + $('#wrn').attr('id') + ')').empty().remove();
        $('#wrn').dialog({autoOpen: false, modal: true, show: "slow", width: 350, height: 200,
            resizable: false, position: 'top', draggable: false,
            buttons: [
                { text: "Ok", click: function() {

                    $.ajax({
                        'type': "GET",
                        'url': url
                    }).done(function () {
                            $('#container').load('/admin/templates', a_bind);
                        });

                    $( this ).dialog( "close" );
            } },

                { text: "Відміна", click: function() {
                    $( this ).dialog( "close" );
                } }
            ],

            open: function () {
                var t = $(this).parent()
                t.offset({
                    top: $(window).height() / 2 - (t.height() / 2),
                    left: ($(window).width() / 2) - (t.width() / 2)
                });
            }
        });


        $("#wrn").dialog('open');

        return false;
    });

    $('.add_template').off();
    $('.add_template').on('click', function () {
        $('#container').load(this.href, function () {
            a_bind();
        });
        return false;
    });

    $('#edit_template_form').off();
    $('#edit_template_form').on('submit', function () {

        var name = $('#db_book_name').val();
        var db_schema = $('#db_schema_content').val();
        var view_template = $('#view_template').val();

        var url = $('#edit_template_form').attr('action');

        $.ajax({
            type: "POST",
            url: url,
            data: {name: name, db_schema: db_schema, view_template: view_template, submit: true}
        }).done(function (res) {
                if (res == '1') {
                    $('#msg').addClass('green');
                    $('#msg').fadeOut(1);
                    $('#msg').text("Збережено");

                    $('#msg').fadeIn(1000, function () {
                        $('#msg').fadeOut(3000);
                    })
                } else {
                    $('#msg').addClass('red');

                    $('#msg').fadeOut(1);
                    $('#msg').text('Помилка');

                    $('#msg').fadeIn(1000, function () {
                        $('#msg').fadeOut(3000);
                    })
                }
                $('#container').load('/admin/templates', a_bind);
            });

        return false;
    });

    $('#templates_button').off();
    $('#templates_button').on('click', function () {
        $('#container').load('/admin/templates', function () {
            a_bind();
        });
        return false;
    });

}

$(document).ready(function () {
    a_bind();
})