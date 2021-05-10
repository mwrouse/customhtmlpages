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
});