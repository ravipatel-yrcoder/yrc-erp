'use strict';
$(function () {
	const select2 = $('.select2');
	if (select2.length) {
		select2.each(function () {
			var $this = $(this);
      var placeholder = $this.data("placeholder") || "Select value";
			$this.wrap('<div class="position-relative"></div>').select2({
				placeholder: placeholder,
				dropdownParent: $this.parent()
			});
		});
	}
});