function error(msg) {
    $('#error_msg').fadeIn(100);
    $('#error_msg').text(msg);
}

function load_search_page() {
    $('#container').load('/index/search', function () {
        function checkColor() {
            if ($('#search').val() == 'Введіть назву чи автора для пошуку')
                $('#search').addClass('input_field_default');
            else
                $('#search').removeClass('input_field_default');
        }

        checkColor();

        $('#search').on('focus', function () {
            if ($('#search').val() == 'Введіть назву чи автора для пошуку')
                $('#search').val('');

            checkColor();
        });

        $('#search').on('blur', function () {
            if ($('#search').val() == '')
                $('#search').val('Введіть назву чи автора для пошуку');
            checkColor();
        });
    });

    return false;
}

/*bind all click functions - for all menu elements
 (and for inside content) and submit button in authorization form
 */

function binder() {

    $('#login_button').off();
    $('#login_button').on('click', function () {
        $('#error_msg').fadeOut(100);
        $("#dialog").dialog('open');
        return false;
    })

    $('#exit_button').off();
    $('#exit_button').on('click', function () {
        $.ajax({
            type: "GET",
            url: "/index/exit"
        }).done(function () {

                $('#menu').load('index/menu', binder)

                $('#container').load('index/search', binder)

                return false;
            });
        return false;

    });

    $('#search_button').off();
    $('#search_button').on('click', function () {
        load_search_page();
        return false;
    });

    $('#login_form').off();
    $('#login_form').on('submit', function () {

        var login = $('#login').val();

        var password = $('#password').val();

        if ((login == '') || password == '') {
            error('Незаповнене одне із полів');
            return false;

        }
        else {
            $.ajax({
                type: "POST",
                url: "/index/login",
                data: { login: login, password: password }
            }).done(function (content) {
                    if (content == '0') {
                        error('Неправильне ім’я користувача або пароль');
                    }
                    else if (content == '1') {
                        $('#menu').load('/index/menu', binder);

                        $("#dialog").dialog('close');
                    }
                })

            return false;
        }
    })

    $('#source_button').off();
    $('#source_button').on('click', function () {

        $('#container').load('/index/sources', function () {

            function bindMultifieldsActions() {

                $('input, textarea').off();
                $('input, textarea').on('focusout', function () {

                    var parent_class = $(this).parent().parent().attr("class");

                    if (parent_class == 'subfields') {

                    }

                    else {

                    }

                });
            };

            $('.add_source').off();
            $('.add_source').on('click', function () {
                $('#container').load('/index/addSource', function () {

                    $('#source_type_select').on('change', function () {
                        $('#source_fields').load('index/loadSourceFields?id=' +
                            $('#source_type_select option:selected').val(), bindMultifieldsActions);
                        return false;
                    });

                    $('#add_source_form').off();
                    $('#add_source_form').on('submit', function () {

                        var data = $(this).serialize();
                        data += '&submit=yes';

                        var url = 'index/addSource';

                        $.ajax({
                            'type': "POST",
                            'url': url,
                            'data': data
                        }).done(function () {
                                $('#container').load('/index/sources', binder);
                                return false;
                            });

                        return false;
                    });

                    $('#source_fields').load('/index/loadSourceFields', bindMultifieldsActions);

                    return false;
                });
                return false;
            });

        });

        return false;
    });

    a_bind();
}

$(document).ready(function () {
    $('#dialog').dialog({autoOpen: false, modal: true, show: "slow", width: 550, height: 250,
        resizable: false, position: 'top', draggable: false,
        open: function () {
            var t = $(this).parent()
            t.offset({
                top: $(window).height() / 2 - (t.height() / 2),
                left: ($(window).width() / 2) - (t.width() / 2)
            });
        }
    });

    $(window).resize(function () {
        $("#dialog").dialog("option", "position", "center");
        var t = $("#dialog").parent()
        t.offset({
            top: $(window).height() / 2 - (t.height() / 2),
            left: ($(window).width() / 2) - (t.width() / 2)
        });
    });

    binder();
    load_search_page();

    $(document).ajaxStart(function () {
        $.blockUI({ message: '<h1><img src="/assets/img/ajax-loader.gif" /> Чекайте...</h1>' });

    });

    $(document).ajaxStop(function () {
        $.unblockUI();
    })
})
