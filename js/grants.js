waitForElm('#grants_table').then((elm) => {
    ajaxDataTable('grantList',{'searchParams':[]},elm.id)
});

/*waitForElm('#modal_table').then((elm) => {
    new DataTable('#modal_table', {
        data: [['author','notebox','07-10-2025']],
        columns: [{'title':'Author'}, {'title':'Comment'}, {'title':'Date'}],
        layout: {
            topStart: '',
            topEnd: ''
        },
        fixedColumns: true,
        paging: false,
        scrollCollapse: true,
        scrollX: false,
        scrollY: 100
    });
});*/

// Get the modal
var modal = document.getElementById("grant_modal");
// Get the <span> element that closes the modal
var span = document.getElementsByClassName("modal_close")[0];
var commentBox = document.getElementById("modal_table");

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

function viewCommentModal(recordId) {
    modal.style.display = "block";
    document.getElementById('comment_record').value = escapeHtml(recordId);
    ajaxDataToTable('getComments',{'record':recordId},'modal_table');
}

function addComment(textboxid) {
    let table = document.getElementById('modal_table');
    let textBox = document.getElementById(textboxid);
    let comment = escapeHtml(textBox.value);
    let commentRecord = escapeHtml(document.getElementById('comment_record').value);
    ajaxCreateRecord('addComment',{'record':commentRecord, 'comment': comment});
    textBox.value = '';
}

var entityMap = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
    '/': '&#x2F;',
    '`': '&#x60;',
    '=': '&#x3D;'
};

function escapeHtml (string) {
    return String(string).replace(/[&<>"'`=\/]/g, function (s) {
        return entityMap[s];
    });
}
