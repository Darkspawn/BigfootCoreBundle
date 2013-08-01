/* Navigation */
$(document).ready(function () {

    $(window).resize(function () {
        if ($(window).width() > 768) {
            $(".sidebar #nav").slideDown(350);
        } else {
            $(".sidebar #nav").slideUp(350);
        }
    });


    $("#nav > li > a").on('click', function (e) {
        if ($(this).parent().hasClass("has_sub")) {
            e.preventDefault();
        }

        if (!$(this).hasClass("subdrop")) {
            // hide any open menus and remove all other classes
            $("#nav li ul").slideUp(350);
            $("#nav li a").removeClass("subdrop");

            // open our new menu and add the open class
            $(this).next("ul").slideDown(350);
            $(this).addClass("subdrop");
        } else if ($(this).hasClass("subdrop")) {
            $(this).removeClass("subdrop");
            $(this).next("ul").slideUp(350);
        }

    });


    $("#nav > li > ul > li > a").on('click', function (e) {
        if ($(this).parent().hasClass("has_sub")) {
            e.preventDefault();
        }

        if (!$(this).hasClass("subdrop")) {
            // hide any open menus and remove all other classes
            $("#nav li ul li ul").slideUp(350);
            $("#nav > li > ul > li > a").removeClass("subdrop");

            // open our new menu and add the open class
            $(this).next("ul").slideDown(350);
            $(this).addClass("subdrop");
        } else if ($(this).hasClass("subdrop")) {
            $(this).removeClass("subdrop");
            $(this).next("ul").slideUp(350);
        }

    });
});

$(document).ready(function () {
    $(".sidebar-dropdown a").on('click', function (e) {
        e.preventDefault();

        if (!$(this).hasClass("open")) {
            // hide any open menus and remove all other classes
            $(".sidebar #nav").slideUp(350);
            $(".sidebar-dropdown a").removeClass("open");

            // open our new menu and add the open class
            $(".sidebar #nav").slideDown(350);
            $(this).addClass("open");
        } else if ($(this).hasClass("open")) {
            $(this).removeClass("open");
            $(".sidebar #nav").slideUp(350);
        }
    });

    $("#nav .has_sub ul li ul li.active")
        .parent().parent().parent().parent().children('a').click();

    $("#nav .has_sub li.active")
        .parent().parent().children('a').click();
});

/* Form collections */
$(document).ready(function () {
    addCollectionItemEvent();
    deleteCollectionItemEvent();
    sortCollection();

    setupCollectionItem();
    $('body').on('click', '.portlet-header > span.toggle-portlet', function() {
        $(this).toggleClass("ui-icon-minusthick").toggleClass("ui-icon-plusthick");
        $(this).closest(".portlet").find(".portlet-content").toggle();
    });
    $(".portlet-content").toggle();
});

// Support for AJAX loaded modal window.
// Focuses on first input textbox after it loads the window.
$(document).ready(function () {
    $('[data-toggle="modal"]').click(function (e) {
        e.preventDefault();
        var url = $(this).attr('href');
        if (url.indexOf('#') == 0) {
            $(url).modal('open');
        } else {
            $.get(url, function (data) {
                $('<div class="modal hide fade">' + data + '</div>').modal();
            }).success(function () {
                    $('input:text:visible:first').focus();
                });
        }
    });

});

/* Portfolio */
$(function() {
    $('.field-media').each(function() {
        $.bigfoot.portfolio(this);
    });

    initSelects();
})

/* Translatable fields */
$(function() {
    var $translatableFields = $('.translatable-fields');
    if ($translatableFields.length) {
        setupTranslatableFields($translatableFields);

        $('#locales-container').html(Twig.render(localeTabs, {locales: locales, currentLocale: currentLocale, basePath: basePath}));

        var $localeTab = $('.locale-tabs');
        $localeTab.on('click', 'a', function(event) {
            event.stopPropagation();

            if (!$(this).hasClass('active')) {
                var newLocale = $(this).data('locale');
                $('input[data-locale="'+newLocale+'"], textarea[data-locale="'+newLocale+'"]').closest('div.input-group').show();
                $('input[data-locale="'+currentLocale+'"], textarea[data-locale="'+currentLocale+'"]').closest('div.input-group').hide();

                $('a', $localeTab).removeClass('active');
                $(this).addClass('active');
                currentLocale = newLocale;
            }

            return false;
        });
    }
})

/* Functions */
function strpos (haystack, needle, offset) {
    var i = (haystack + '').indexOf(needle, (offset || 0));

    return i === -1 ? false : i;
}

function setupTranslatableFields($translatableFields) {
    $translatableFields.hide();
    // Getting all translated fields to set their parent's data attributes (default locale fields aren't initialized by the translationsubscriber)
    $('input[type="text"], textarea', $translatableFields).each(function() {
        var elementId = $(this).attr('id')
        var parentElementId = elementId.substr(0, elementId.lastIndexOf('-')).replace('_translation', '');

        var $parentElement = $('#'+parentElementId);

        $parentElement
            .data('locale', currentLocale)
            .attr('data-locale', currentLocale);

        $(this).appendTo($parentElement.parent());
    });

    var $wrapper = $('<div class="input-group"></div>');
    var $toWrap = $('input[data-locale], textarea[data-locale]');
    $toWrap.wrap($wrapper);
    $toWrap.each(function() {
        $(this).after($('<span class="input-group-addon"><img src="/bundles/bigfootcore/img/flags/'+$(this).data('locale')+'.gif" /></span>'));
        if ($(this).data('locale') != currentLocale) {
            $(this).closest('div.input-group').hide();
        }
    });
}

function sortCollection() {
    var $sortableFields = $('input.sortable-field');
    if ($sortableFields.length > 0) {
        $sortableFields.closest('div.portlet').parent('div').parent('div').each(function() {
            $(this).sortable({
                update: function () {
                    var inputs = $('input.sortable-field');
                    var nbElems = inputs.length;
                    $('input.sortable-field').each(function(idx) {
                        $(this).val(idx);
                    });
                }
            });
        });

        $sortableFields.each(function() {
            $(this).addClass( "ui-widget ui-widget-content ui-helper-clearfix ui-corner-all" );
        });
    }
}

function setupCollectionItem() {
    $(".portlet:not(.ui-widget)").addClass("ui-widget ui-widget-content ui-helper-clearfix ui-corner-all")
        .find(".portlet-header")
        .addClass("ui-widget-header ui-corner-all")
        .prepend("<span class='ui-icon ui-icon-plusthick toggle-portlet'></span>");
}

function addCollectionItemEvent() {
    $('a.addCollectionItem').on('click', function (event) {
        event.preventDefault();
        addCollectionItem($(this).data('collection-id'));
    });
}

function deleteCollectionItemEvent() {
    $('a.deleteCollectionItem').on('click', function (event) {
        event.preventDefault();
        $(this).parent('div').parent('div').remove();
    });
}

function addCollectionItem(id) {

    var collectionHolder = $(id);

    var prototype = collectionHolder.attr('data-prototype');
    form = prototype.replace(/__name__/g, collectionHolder.children().length);

    collectionHolder.append(form);

    deleteCollectionItemEvent();

    $(id).find('a.addCollectionItem').on('click', function (event) {
        event.preventDefault();
        addCollectionItem($(this).data('collection-id'));
    });

    setupSortable();
}
