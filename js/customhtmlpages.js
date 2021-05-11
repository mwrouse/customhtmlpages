function buildURLOfParent(parentId)
{
    if (parentId == 0 || parentId == null)
        return "/";

    var parent = null;
    for (var i = 0; i < allCustomHTMLPages.length; i++) {
        if (allCustomHTMLPages[i].id_page == parentId)
        {
            parent = allCustomHTMLPages[i];
            break;
        }
    }

    if (parent == null)
        return "/";

    var before = buildURLOfParent(parent['id_parent']);

    return before + parent['url'] + "/";
}

function updateURL()
{
    var slug = $('input#url').val();
    var parent = $('select#id_parent').val();

    var urlContainer = $('#full-url');

    var url = buildURLOfParent(parent) + slug;
    urlContainer.html(
        '<a href="' + url + '" target="_blank">' + url + '</a>'
    );
}


function betterSwapBinding($el) {
    $el.on('click', function(e) {
        e.preventDefault();

        var $select = $el.prev();
        var name = $select.attr('name');

        var isAdd = name.indexOf('_available[]') != -1;
        var isRemove = name.indexOf('_selected[]') != -1;

        if (!isAdd && !isRemove)
            return;

        name = (name.replaceAll('_selected[]', '')).replaceAll('_available[]', '');

        var $removeFrom = $select;
        var $addTo = $('select[name="' + (name + (isAdd ? '_selected[]' : '_available[]')) + '"]').first();

        // Remove ones to be removed
        $removeFrom.find('option:selected').each(function() {
            $addTo.append('<option value="' + $(this).val() + '" selected="true">' + $(this).text() + '</option>');
            $(this).remove();
        });
    });
}


window.addEventListener('load', function(){
    if (!document.body.classList.contains('admincustomhtmlpages') || allCustomHTMLPages == undefined)
        return;

    var inputURL = $('input#url');
    var parentSelect = $('select#id_parent');

    var url = buildURLOfParent(parentSelect.val()) + inputURL.val();

    inputURL.after('<div>Full URL: <span id="full-url" style="font-style: italic"></span></div>');
    updateURL();


    inputURL.on('keyup', function() {
        updateURL();
    });

    parentSelect.on('change', function() {
        updateURL();
    });

    $('[id="addSwap"]').each(function(){
        // Remove ID and click binding (for if old swap has already been registered, or not yet registered)
        $(this).removeAttr('id');
        $(this).unbind('click');
        betterSwapBinding($(this));

    });

    $('[id="removeSwap"]').each(function(){
        // Remove ID and click binding (for if old swap has already been registered, or not yet registered)
        $(this).removeAttr('id');
        $(this).unbind('click');

        betterSwapBinding($(this));
    });
});