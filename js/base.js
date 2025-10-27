const module = ExternalModules.Vanderbilt.GrantRepository;

function ajaxDataToTable(ajax_function,payload,dest_id) {
    document.getElementById('comment_loader').style.display = 'block';
    module.ajax(ajax_function, payload).then((response) => {
        let table = document.getElementById(dest_id);
        while(table.hasChildNodes())
        {
            console.log('removing rows');
            table.removeChild(table.firstChild);
        }

        let header = table.createTHead();
        let headerRow = header.insertRow(0);
        for (let i = 0; i < response['headers'].length; i++) {
            headerRow.insertCell(i).innerHTML = response['headers'][i];
        }

        document.getElementById('comment_loader').style.display = 'none';
        let tbody = document.createElement("tbody");
        console.log(response['rows']);
        for (const row in response['rows']) {
            if (response['rows'][row]['info'] != undefined && response['rows'][row]['comment'] != undefined) {
                let newRow = tbody.insertRow();
                newRow.insertCell(0).innerHTML = response['rows'][row]['info'];
                newRow.insertCell(1).innerHTML = response['rows'][row]['comment'];
                table.appendChild(tbody);
            }
        }
    }).catch((err) => {
        console.log(err);
    })
}

function ajaxDataTable(ajax_function,payload,dest_id) {
    // Default sort PI then title
    //console.log(payload);
    module.ajax(ajax_function, payload).then((response) => {
        //console.log(response);
        if (response['data'] && response['columns']) {
            new DataTable('#'+dest_id, {
                data: response['data'],
                columns: response['columns'],
                layout: {
                    topStart: function () {
                        return 'Filter by Award:&nbsp;<select id="award_select"></select>'
                    },
                    topEnd: {
                        search: {},
                        buttons: [{
                            extend: 'colvis',
                            text: 'Column Visibility'
                        }]
                    }
                },
                destroy: true,
                fixedColumns: true,
                paging: false,
                scrollCollapse: true,
                scrollX: false,
                scrollY: 450,
                bAutoWidth: false,
                columnDefs: [
                    {
                        targets: [4,7],
                        searchable: true,
                        visible: false
                    }],
                initComplete: function () {
                    let column = this.api().column(4);
                    //console.log(column);
                    let select = document.getElementById('award_select');

                    select.addEventListener('change', function () {
                        column
                            .search(select.value, {exact: true})
                            .draw();
                    });

                    column
                        .data()
                        .unique()
                        .sort()
                        .each(function (d, j) {
                            select.add(new Option(d));
                        });
                }
            });
        }
    }).catch((err) => {
        console.log(err);
    })
}

function ajaxCreateRecord(ajax_function,payload) {
    let table = document.getElementById('modal_table');
    while(table.hasChildNodes())
    {
        console.log('removing rows');
        table.removeChild(table.firstChild);
    }
    document.getElementById('comment_loader').style.display = 'block';
    module.ajax(ajax_function,payload).then((response) => {
        if (response) {
            ajaxDataToTable('getComments', {'record': payload['record']}, 'modal_table');
        }
    }).catch((err) => {
        console.log(err);
    })
}

function waitForElm(selector) {
    return new Promise(resolve => {
        if (document.querySelector(selector)) {
            return resolve(document.querySelector(selector));
        }

        const observer = new MutationObserver(mutations => {
            if (document.querySelector(selector)) {
                observer.disconnect();
                resolve(document.querySelector(selector));
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
}

function pageRedirect(page) {
    module.ajax('redirect', {'page':page}).then((response) => {
        if (response.hasOwnProperty('result')) {
            window.location.href = response['result'];
        }
        else {
            alert(response['errors'])
        }
    }).catch((err) => {
        console.log(err);
    })
}

function logFileDownload(record,userid) {
    module.ajax('logFileDownload', {'record':record,'userid':userid}).then((response) => {
        console.log(response);
    }).catch((err) => {
        console.log(err);
    })
}

function downloadFile(fileObject,record,userid,csrf_token) {
    const url = module.getUrl('downloadFile');

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.target = '_blank'; // Opens in a new tab

    for (const key in fileObject) {
        if (fileObject.hasOwnProperty(key)) {
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = key;
            hiddenField.value = fileObject[key];
            form.appendChild(hiddenField);
        }
    }
    let csrfField = document.createElement('input');
    csrfField.type = 'hidden';
    csrfField.name = 'redcap_csrf_token';
    csrfField.value = csrf_token;
    form.appendChild(csrfField);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
