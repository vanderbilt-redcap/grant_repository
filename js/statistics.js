waitForElm('#stats_table').then((elm) => {
    ajaxHTMLToElement('statResults',{'searchParams':[]},elm.id)
});