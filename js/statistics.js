function toggleDrilldown(target_id) {
    let target = document.getElementById(target_id);

    if (target.style.display === "none" || target.style.display === "") {
        target.style.display = "block";
    } else {
        target.style.display = "none";
    }
}
