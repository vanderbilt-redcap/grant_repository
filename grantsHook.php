<script>
$(document).ready(function() {
	$('[name=grants_number]').blur(function() {
		showCheckboxes();
	});
	function showCheckboxes() {
		var val = $('[name=grants_number]').val();
		console.log("showCheckboxes with "+val);
		if (val.match(/K\d\d/)) {
			$('#k_awards-tr').show();
			$('#r_awards-tr').hide();
			$('#misc_awards-tr').hide();
			$('#lrp_awards-tr').hide();
			$('#va_merit_awards-tr').hide();
		} else if (val.match(/R\d\d/)) {
			$('#k_awards-tr').hide();
			$('#r_awards-tr').show();
			$('#misc_awards-tr').hide();
			$('#lrp_awards-tr').hide();
			$('#va_merit_awards-tr').hide();
		} else if (val.match(/LRP/)) {
			$('#k_awards-tr').hide();
			$('#r_awards-tr').hide();
			$('#misc_awards-tr').hide();
			$('#lrp_awards-tr').show();
			$('#va_merit_awards-tr').hide();
		} else if (val.match(/I01/)) {
			$('#k_awards-tr').hide();
			$('#r_awards-tr').hide();
			$('#misc_awards-tr').hide();
			$('#lrp_awards-tr').hide();
			$('#va_merit_awards-tr').show();
		} else if (val) {
			$('#k_awards-tr').hide();
			$('#r_awards-tr').hide();
			$('#misc_awards-tr').show();
			$('#lrp_awards-tr').hide();
			$('#va_merit_awards-tr').hide();
		}
	}

	showCheckboxes();
});
</script>
