<script>
$(document).ready(function() {
	console.log("Included");
	$('[name=grants_number]').blur(function() {
		var val = $('[name=grants_number]').val();
		console.log("blur with "+val);
		if (val.match(/K\d\d/)) {
			$('#k_awards-tr').show();
			$('#r01_awards-tr').hide();
			$('#misc_awards-tr').hide();
			$('#lrp_awards-tr').hide();
			$('#va_merit_awards-tr').hide();
		} else if (val.match(/R01/)) {
			$('#k_awards-tr').hide();
			$('#r01_awards-tr').show();
			$('#misc_awards-tr').hide();
			$('#lrp_awards-tr').hide();
			$('#va_merit_awards-tr').hide();
		} else if (val.match(/LRP/)) {
			$('#k_awards-tr').hide();
			$('#r01_awards-tr').hide();
			$('#misc_awards-tr').hide();
			$('#lrp_awards-tr').show();
			$('#va_merit_awards-tr').hide();
		} else if (val.match(/I01/)) {
			$('#k_awards-tr').hide();
			$('#r01_awards-tr').hide();
			$('#misc_awards-tr').hide();
			$('#lrp_awards-tr').hide();
			$('#va_merit_awards-tr').show();
		} else if (val) {
			$('#k_awards-tr').hide();
			$('#r01_awards-tr').hide();
			$('#misc_awards-tr').show();
			$('#lrp_awards-tr').hide();
			$('#va_merit_awards-tr').hide();
		}
	});
});
</script>
