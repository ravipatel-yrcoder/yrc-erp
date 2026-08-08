const UNAUTHORIZED_MESSAGE = "Your session has expired. Please sign in again";
const ACCESS_DENIED_MESSAGE = "You do not have permission to access this resource";
const DELETE_CONFIRM_MESSAGE = "Are you sure you want to delete this item?";

// Global instance for Notyf - Notification
window.notyf = new Notyf({
    duration: 6000,
    ripple: true,
    dismissible: true,
    position: { x: 'left', y: 'top' }
});


const handleApiError = function(error, formElement=null) {

    _error = error;
    _formElement = formElement;
    if (error.response) {
            
        const { code, message, errors={} } = error.response.data;
        
        if( code == 401 ) {
            notyf.error(UNAUTHORIZED_MESSAGE);
            return;
        }
        else if( code == 403 ) {
            notyf.error(ACCESS_DENIED_MESSAGE);
            return;
        }

        if( message ) {
            notyf.error(message);
        }

        if( formElement )
        {
            // Normalize jQuery form to native DOM element
            if (window.jQuery && formElement instanceof jQuery) {
                formElement = formElement[0];
            }

            // Validate it's a real DOM element
            if (formElement instanceof Element) {

                //console.log(errors);
                //_errors = errors;

                for(const [key, value] of Object.entries(errors)) {
                    //console.log(key);
                    const escapedKey = CSS.escape(key);
                    const inputEl = formElement.querySelector(`[name="${escapedKey}"], .${escapedKey}.dropzone`);
                    if( inputEl ) {
                        showFormInputFeedback(inputEl, value);
                    } else {

                        const sectionKey = escapedKey.split(".")[0];
                        const sectionEl = formElement.querySelector(`.${sectionKey}-section-feedback.form-section-feedback`);
                        if( sectionEl ) {
                            showFormSectionFeedback(sectionEl, value);
                        } else {
                            showFormGlobalFeedback(formElement, value);
                        }
                    }
                }
            }
        }

    } else {
                    
        notyf.error("Something went wrong. Please try again later");
        console.log(error);
    }

}


const showFormInputFeedback = function(input, message, type = 'error') {
    
    if (!input) return;

    //_input = input;

    const feedbackClass = type === 'error' ? 'is-invalid' : 'is-valid';

    // Remove previous validation classes and add current
    input.classList.remove('is-invalid', 'is-valid');
    input.classList.add(feedbackClass);

    const dropzoneInput = input.classList.contains('dropzone');
    if( dropzoneInput === true )
    {
        const selector = '.' + Array.from(input.classList).join('.');
        const dropzoneInstance = getDropzoneInstance(selector);
        if( dropzoneInstance )
        {
            if (dropzoneInstance.files.length > 0) {
                const file = dropzoneInstance.files[0];
                dropzoneInstance.emit("error", file, message);
                //file.previewElement.classList.add("dz-error");
                //const errMsg = file.previewElement.querySelector("[data-dz-errormessage]");
                //if (errMsg) errMsg.textContent = message;
            } else {
                dropzonenIstance.element.classList.add("is-invalid");
                let errorEl = dropzoneInstance.element.querySelector(".dz-server-error");
                if (!errorEl) {
                    errorEl = document.createElement("div");
                    errorEl.className = "dz-server-error text-danger mt-1";
                    dropzoneInstance.element.appendChild(errorEl);
                }
                errorEl.textContent = message;
            }
        }
    }
    else
    {
        const inputName = (input.name || '').replace(/\s+/g, '-').replace(/\[\]$/, '').toLowerCase();            

        const feedbackDivId = inputName ? `${inputName}-feedback` : 'feedback';

        // Find or create feedback div
        let feedback = input.parentElement.querySelector(`#${feedbackDivId}`);
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.id = feedbackDivId;
            feedback.className = type === 'error' ? 'invalid-feedback' : 'valid-feedback';

            const parent = input.parentElement;

            // Check if parent has .form-check class
            if (parent.classList.contains('form-check')) {
                
                // Find closest .form-check-group ancestor
                const groupParent = parent.closest('.form-check-group');
                if (groupParent) {
                    feedback.classList.add('d-block');
                    groupParent.appendChild(feedback);
                } else {
                    parent.appendChild(feedback);
                }

            } else {
                parent.appendChild(feedback);
            }
            
        }

        feedback.textContent = message;
    }

    
};

const showFormSectionFeedback = function(sectionEl, message, type = 'error') {
    
    if ( !sectionEl ) return;

    if (!sectionEl.classList.contains('has-feedback')) {
        sectionEl.classList.add('has-feedback');
    }

    let feedback = document.createElement('div');
    feedback.className = type === 'error' ? 'invalid-feedback' : 'valid-feedback';
    sectionEl.appendChild(feedback);

    feedback.textContent = message;
}

const showFormGlobalFeedback = function(formEl, message, type = 'error') {
    
    if ( !formEl ) return;

    const globFeedbackDiv = formEl.querySelector('.form-glob-feedback');
    if( !globFeedbackDiv ) return;

    if (!globFeedbackDiv.classList.contains('has-feedback')) {
        globFeedbackDiv.classList.add('has-feedback');
    }

    let feedback = document.createElement('div');
    feedback.className = type === 'error' ? 'invalid-feedback' : 'valid-feedback';
    globFeedbackDiv.appendChild(feedback);

    feedback.textContent = message;
}

const cleanFormInputFeedback = function(formEl) {

    if( !formEl ) return;

    // Remove validation classes from all inputs, selects, textareas
    formEl.querySelectorAll('input, select, textarea, .dropzone.is-invalid, .dropzone.is-valid').forEach(input => {
        input.classList.remove('is-invalid', 'is-valid');
    });

    // Remove all feedback divs
    formEl.querySelectorAll('.invalid-feedback, .valid-feedback').forEach(fb => fb.remove());

    // remove global feedback div
    const globFeedbackDiv = formEl.querySelector('.form-glob-feedback');
    if (globFeedbackDiv) {
        globFeedbackDiv.classList.remove('has-feedback');
        globFeedbackDiv.html = "";
    }

    // remove section feedback divs
    formEl.querySelectorAll('.form-section-feedback').forEach(el => {
        el.classList.remove('has-feedback');
        el.html = "";
    });

}

const showConfirmation = function(message, type, confirmObj={}, cancelObj={}, params={}) {

	let showCancelBtn = !cancelObj === false;

	let confirmBtnText = confirmObj["text"] || "Yes";
	let confirmBtnClass = confirmObj["class"] || "btn-label-primary";
	let confirmCallback = confirmObj["callback"] || function(){};

	let cancelBtnText = cancelObj["text"] || "Cancel";
	let cancelBtnClass = cancelObj["class"] || "btn-label-secondary";
	let cancelCallback = cancelObj["callback"] || function(){};

	let width = params["width"] || "25em";
	let htmlContainer = params["htmlContainer"] || "text-dark";

	let inputType = params["input"] || null;
	let inputLabel = params["inputLabel"] || null;
	let inputPlaceholder = params["inputPlaceholder"] || '';
	let inputRequired = params["inputRequired"] || false;
	let inputValue = params["inputValue"] ?? '';

	const swalConfig = {
        html: message,
        icon: type,
        buttonsStyling: false,
		showCancelButton: showCancelBtn,
        confirmButtonText: confirmBtnText,
		cancelButtonText: cancelBtnText,
		width: width,
		customClass: {
			confirmButton: "btn btn-sm "+confirmBtnClass,
			cancelButton: "btn btn btn-sm "+cancelBtnClass,
            htmlContainer: htmlContainer,
            popup: 'app-swal-confirmation',
		}
    };

	if (inputType) {
		swalConfig.input = inputType;
		if (inputLabel) swalConfig.inputLabel = inputLabel;
		swalConfig.inputPlaceholder = inputPlaceholder;
		if (inputValue !== '') swalConfig.inputValue = inputValue;
		if (inputType === 'textarea') swalConfig.inputAttributes = { rows: 3 };
		if (inputRequired) {
			swalConfig.inputValidator = (value) => {
				if (!value || !value.trim()) return 'This field is required';
			};
		}
	}

    Swal.fire(swalConfig).then((result) => {

		if (result.isConfirmed) {

			// Execute the callback function if provided
            if (confirmCallback && typeof confirmCallback === 'function') {
                confirmCallback(inputType ? (result.value || '') : undefined);
            }
        }
		else if (result.isDismissed)
		{
			// Execute the callback function if provided
            if (cancelCallback && typeof cancelCallback === 'function') {
                cancelCallback();
            }
		}
    });
};


const buildFormDialogField = function(f, isLast = false) {

    const label = `<label class="form-label small fw-medium">${f.label}${f.required ? ' <span class="text-danger">*</span>' : ''}</label>`;
    const fb    = `<div class="invalid-feedback"></div>`;
    let   input = '';

    if (f.type === 'number') {
        const step = f.step !== undefined ? f.step : 1;
        input = `<input type="number" step="${step}" class="form-control form-control-sm"
                     data-field-key="${f.key}" placeholder="${f.placeholder || ''}"
                     value="${f.value ?? ''}">`;
    } else if (f.type === 'select') {
        input = `<select class="form-select form-select-sm" data-field-key="${f.key}"></select>`;
    } else if (f.type === 'textarea') {
        input = `<textarea class="form-control form-control-sm" rows="3"
                     data-field-key="${f.key}"
                     placeholder="${f.placeholder || ''}">${f.value ?? ''}</textarea>`;
    } else if (f.type === 'date') {
        input = `<input type="text" class="form-control form-control-sm"
                     data-field-key="${f.key}"
                     placeholder="${f.placeholder || 'Select date'}" readonly>`;
    } else {
        input = `<input type="${f.type || 'text'}" class="form-control form-control-sm"
                     data-field-key="${f.key}"
                     placeholder="${f.placeholder || ''}"
                     value="${f.value ?? ''}">`;
    }

    return `<div ${isLast ? '' : 'class="mb-3"'} data-field-wrap="${f.key}">${label}${input}${fb}</div>`;
};


const showFormDialog = function(options = {}) {

    const modalEl   = document.getElementById('appFormDialog');
    const titleEl   = document.getElementById('appFormDialogTitle');
    const descEl    = document.getElementById('appFormDialogDescription');
    const bodyEl    = document.getElementById('appFormDialogBody');
    const cancelBtn = document.getElementById('appFormDialogCancelBtn');

    const fields         = options.fields        || [];
    const confirmText    = options.confirmText    || 'Save';
    const confirmClass   = options.confirmClass   || 'btn-primary';
    const cancelText     = options.cancelText     || 'Cancel';
    const callback       = options.callback       || function() {};
    const cancelCallback = options.cancelCallback || null;

    // Destroy existing Flatpickr instances to prevent duplicate pickers on re-open
    modalEl.querySelectorAll('input._flatpickr, input[data-field-key]').forEach(el => {
        if (el._flatpickr) el._flatpickr.destroy();
    });

    // Destroy existing Select2 instances
    modalEl.querySelectorAll('select').forEach(el => {
        if ($(el).data('select2')) $(el).select2('destroy');
    });

    // Set title and button labels
    titleEl.textContent   = options.title || '';
    cancelBtn.textContent = cancelText;

    // Description (optional — HTML or plain text shown above fields)
    if (options.description) {
        descEl.innerHTML     = options.description;
        descEl.style.display = '';
    } else {
        descEl.innerHTML     = '';
        descEl.style.display = 'none';
    }

    // Clone save button to drop stale listeners from previous open
    const oldSaveBtn = document.getElementById('appFormDialogSaveBtn');
    const saveBtn    = oldSaveBtn.cloneNode(true);
    saveBtn.textContent = confirmText;
    saveBtn.className   = `btn btn-sm ${confirmClass}`;
    oldSaveBtn.replaceWith(saveBtn);

    // Build field HTML — last field gets no mb-3
    bodyEl.innerHTML = fields.map((f, idx) => buildFormDialogField(f, idx === fields.length - 1)).join('');

    // Initialize pickers on date and select fields
    fields.forEach(f => {
        if (f.type === 'date') {
            initDatePicker(`#appFormDialog [data-field-key="${f.key}"]`);
            if (f.value) datePickerSetDate(`#appFormDialog [data-field-key="${f.key}"]`, f.value);
        }
        if (f.type === 'select') {
            initSelect2(`#appFormDialog [data-field-key="${f.key}"]`, {
                dropdownParent: modalEl,
                data: buildSelect2Options(f.options || [], { idKey: 'id', textKey: 'name' }),
                allowClear: f.allowClear || false,
                placeholder: f.placeholder || '— Select —',
            });
            if (f.value) $(`#appFormDialog [data-field-key="${f.key}"]`).val(f.value).trigger('change');
        }
    });

    // Show modal
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    // Save handler
    saveBtn.addEventListener('click', function() {

        // Clear previous validation state
        bodyEl.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        bodyEl.querySelectorAll('.invalid-feedback').forEach(el => { el.textContent = ''; });

        let valid = true;
        const values = {};

        fields.forEach(f => {
            const el = bodyEl.querySelector(`[data-field-key="${f.key}"]`);
            if (!el) return;

            const val = (el.value ?? '').trim();

            if (f.required && val === '') {
                el.classList.add('is-invalid');
                const fb = bodyEl.querySelector(`[data-field-wrap="${f.key}"] .invalid-feedback`);
                if (fb) fb.textContent = `${f.label} is required`;
                valid = false;
                return;
            }

            if (f.type === 'number' || f.type === 'currency') {
                values[f.key] = val !== '' ? parseFloat(val) : null;
            } else {
                values[f.key] = val || null;
            }
        });

        if (!valid) return;

        modal.hide();
        callback(values);
    });

    // One-time cancel callback via hidden event
    if (cancelCallback) {
        const onHidden = () => {
            cancelCallback();
            modalEl.removeEventListener('hidden.bs.modal', onHidden);
        };
        modalEl.addEventListener('hidden.bs.modal', onHidden);
    }
};


const ucFirst = function(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}


/**
 * Put a button into loading state (disabled + spinner) or restore it.
 * Call with loading=true before an async API call, then always call with
 * loading=false in the finally block so the button is always restored.
 *
 * Usage:
 *   setButtonLoading(btn, true);
 *   try { await api.post(...); } catch(e) { ... } finally { setButtonLoading(btn, false); }
 */
const setButtonLoading = function(btn, loading, loadingText = 'Processing...') {
    if (!btn) return;
    if (loading) {
        btn.disabled = true;
        btn.dataset.originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + loadingText;
    } else {
        btn.disabled = false;
        if (btn.dataset.originalHtml !== undefined) {
            btn.innerHTML = btn.dataset.originalHtml;
            delete btn.dataset.originalHtml;
        }
    }
};


/**
 * Form related common function
 */
const buildCategorySelect2Options = function(categories, level = 0) {

    let result = [];
    const prefix = "— ".repeat(level); // add visual indentation

    categories.forEach(cat => {
        result.push({
            id: cat.id,
            text: prefix + cat.category
        });

        if (cat.children && cat.children.length > 0) {
            result = result.concat(buildCategorySelect2Options(cat.children, level + 1));
        }
    });

    return result;
}

const getDropzoneInstance = function(selector) {
  
    const el = document.querySelector(selector);
    if (!el) {
        return null;
    }

    const dz = Dropzone.forElement ? Dropzone.forElement(el) : null;
    if (dz && dz instanceof Dropzone) {
        return dz;
    }

    return null;
}

const populateDropzoneImage = function(dropzoneInstance, imageUrl) {

    if ( !(dropzoneInstance && dropzoneInstance instanceof Dropzone) ) return;
    if (!imageUrl) return;

    const fileName = imageUrl.split('/').pop().split('?')[0];
    const mockFile = { name: fileName, size: 0 };

    dropzoneInstance.emit("addedfile", mockFile);
    dropzoneInstance.emit("thumbnail", mockFile, imageUrl);
    dropzoneInstance.emit("complete", mockFile);

    mockFile.status = Dropzone.SUCCESS;
    mockFile.existing = true;
    mockFile.image_url = imageUrl;
    dropzoneInstance.files.push(mockFile);
}

/**
 * Initialize or re-initialize a Select2 dropdown.
 *
 * This utility is mainly used inside drawer/offcanvas forms where Select2
 * must be refreshed when the drawer is opened (or re-opened).
 *
 */

const initSelect2 = function(selector, options={}) {

    //console.log(options);

    // destry if aready initiated
    select2El = jQuery(selector);
    if (select2El.data("select2")) {

        select2El.off("change.select2Custom"); // remove custom change handler

        // only empty select2 options if data is supplied
        if( typeof(options.data) !== "undefined" ) {
            select2El.empty();
            //console.log("reset data");
        }

        select2El.select2("destroy");
    }

    const defaultOptions = {
        placeholder: 'Choose option',
        width: '100%',
        allowClear: true,
    }

    // Extract onChange callback (custom option)
    const { onChange, ...select2Options } = options;
    
    // Merge defaults with custom options
    const finalOptions = Object.assign({}, defaultOptions, select2Options);

    //console.log(finalOptions);

    // Initialize Select2
    select2El.select2(finalOptions);

    // Bind change handler if provided
    if (typeof onChange === "function") {
        select2El.on("change.select2Custom", function () {
            onChange(this, jQuery(this).select2("data"), jQuery(this));
        });
    }

    const data = options.data || [];
    if( options.autoSelectSingle === true && Array.isArray(data) && data.length === 1 ) {        
        select2El.val(data[0].id || null).trigger('change');        
        return;
    }

    if( options.resetVal !== false ) {
        select2El.val(null).trigger('change');
    }    
}


/**
 * Date Picker
 */
const initDatePicker = function (selector, options = {}) {
    
    const sysDateFormat = window.sysDefaultConfig?.dateFormat || 'd/m/Y'; 
    
    jQuery(selector).flatpickr({
        static: true,
        altInput: true,
        altFormat: sysDateFormat,
        dateFormat: "Y-m-d",
        ...options
    });
};

const datePickerSetDate = function(selector, date) {

    try {

        if (!date) return;

        const el = jQuery(selector)[0];
        const instance = el?._flatpickr;

        instance?.setDate(date, true); // ISO date

    } catch(err) {}

}

const initTimePicker = function (selector, options = {}) {

    jQuery(selector).flatpickr({
        static: true,
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",   // submitted value — 24hr, backend-compatible
        altInput: true,
        altFormat: "h:i K",  // display — 12hr with AM/PM
        time_24hr: false,
        ...options
    });
};

const timePickerSetTime = function (selector, value) {

    try {

        if (!value) return;

        const el = jQuery(selector)[0];
        const instance = el?._flatpickr;

        instance?.setDate(value, true); // value in H:i (24hr)

    } catch(err) {}

};

/**
 * Parse a MySQL datetime string (YYYY-MM-DD HH:MM:SS) that is stored in `sourceTz`
 * and return the equivalent UTC Date object.
 */
const parseDateInTz = function(dateStr, sourceTz) {
    // Use Intl to find what UTC offset the given timezone had at the wall-clock moment
    // described by dateStr, then back-compute the UTC timestamp.
    const [datePart, timePart = '00:00:00'] = dateStr.split(' ');
    const [year, month, day]   = datePart.split('-').map(Number);
    const [hour, minute, second] = timePart.split(':').map(Number);

    // Approximate UTC epoch (may be off by one DST hour)
    const approxUtc = Date.UTC(year, month - 1, day, hour, minute, second);

    // Get what Intl says the local time is at approxUtc in sourceTz
    const parts = new Intl.DateTimeFormat('en-US', {
        timeZone: sourceTz,
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hour12: false,
    }).formatToParts(new Date(approxUtc));

    const p = {};
    parts.forEach(({ type, value }) => { p[type] = value; });
    const tzHour = p.hour === '24' ? 0 : parseInt(p.hour, 10);
    const localMs = Date.UTC(
        parseInt(p.year, 10), parseInt(p.month, 10) - 1, parseInt(p.day, 10),
        tzHour, parseInt(p.minute, 10), parseInt(p.second, 10)
    );
    // Offset = what UTC clock reads when tz shows localMs-equivalent wall time
    return new Date(approxUtc + (approxUtc - localMs));
};

/**
 * Convert a UTC Date to a "fake local" Date whose local getHours/getDate etc.
 * match the wall-clock time in `displayTz`. Used so flatpickr.formatDate()
 * renders the correct time regardless of the browser's own timezone.
 */
const tzDisplayDate = function(utcDate, displayTz) {
    const parts = new Intl.DateTimeFormat('en-US', {
        timeZone: displayTz,
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hour12: false,
    }).formatToParts(utcDate);

    const p = {};
    parts.forEach(({ type, value }) => { p[type] = value; });
    const h = p.hour === '24' ? 0 : parseInt(p.hour, 10);
    // Build a Date whose local fields (in *this* browser) equal the tz wall time
    return new Date(
        parseInt(p.year, 10), parseInt(p.month, 10) - 1, parseInt(p.day, 10),
        h, parseInt(p.minute, 10), parseInt(p.second, 10)
    );
};

/**
 * Format a MySQL date/datetime string for display.
 *
 * @param {string}      date       MySQL date "YYYY-MM-DD" or datetime "YYYY-MM-DD HH:MM:SS"
 * @param {string|null} format     PHP-style format token (falls back to sysDefaultConfig)
 * @param {string}      fallback   Value returned when date is empty/invalid
 * @param {string|null} sourceTz   IANA timezone the datetime is stored in (e.g. "UTC").
 *                                 When provided for datetime values, the output is converted
 *                                 to window.companyTimezone for display.
 *                                 When null, the string is treated as already in local/display time.
 */
const formatMySqlDate = function (date, format = null, fallback = '-', sourceTz = null) {

    if (!date) return fallback;

    try {
        const sysDateFormat = window.sysDefaultConfig.dateFormat;
        const sysDateTimeFormat = window.sysDefaultConfig?.dateTimeFormat;

        let parsedDate = null;
        let hasTime = false;

        // MySQL DATETIME: YYYY-MM-DD HH:MM:SS
        if (/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/.test(date)) {
            hasTime = true;
            if (window.companyTimezone) {
                // Default: treat as UTC, convert to company timezone. sourceTz overrides source if needed.
                const utcDate = parseDateInTz(date, sourceTz || 'UTC');
                parsedDate = tzDisplayDate(utcDate, window.companyTimezone);
            } else {
                // No company TZ available (public pages) — old behavior
                parsedDate = new Date(date.replace(' ', 'T'));
            }
        }
        // MySQL DATE: YYYY-MM-DD
        else if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
            parsedDate = new Date(`${date}T00:00:00`);
            hasTime = false;
        } else {
            return fallback;
        }

        if (isNaN(parsedDate.getTime())) return fallback;

        // Decide output format
        const phpFormat =
            format ||
            (hasTime ? sysDateTimeFormat : sysDateFormat);

        // sys_default.php uses PHP date tokens. Translate differing tokens to
        // their Flatpickr equivalents before passing to flatpickr.formatDate().
        const flatpickrFormat = phpFormat
            .replace(/a/g, 'K')  // am/pm lowercase → Flatpickr AM/PM
            .replace(/A/g, 'K')  // AM/PM uppercase → Flatpickr AM/PM
            .replace(/g/g, 'G')  // 12-hour no leading zero
            .replace(/s/g, 'S'); // seconds

        return flatpickr.formatDate(parsedDate, flatpickrFormat);

    } catch (err) {
        return fallback;
    }
};



const unformatNumber = function(value) {

    if (!value) return 0;

    return Number(
        value
            .toString()
            .replace(/[^0-9.-]/g, '')
    ) || 0;
}


const formatCurrency = function(value, options = {}) {

    const {
        currency = window.sysDefaultConfig.currency,
        locale = window.sysDefaultConfig.locale,
        minimumFractionDigits = 2,
        maximumFractionDigits = 4
    } = options;

    const amount = Number(value);

    if (Number.isNaN(amount)) {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency,
            minimumFractionDigits,
            maximumFractionDigits
        }).format(0);
    }

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        minimumFractionDigits,
        maximumFractionDigits
    }).format(amount);
}



const formatPrice = function(value, options = {}) {

    const {
        locale = window.sysDefaultConfig.locale,
        minimumFractionDigits = 2,
        maximumFractionDigits = 4
    } = options;

    const amount = Number(value);

    if (Number.isNaN(amount)) {
        return new Intl.NumberFormat(locale, {
            minimumFractionDigits,
            maximumFractionDigits
        }).format(0);
    }

    return new Intl.NumberFormat(locale, {
        minimumFractionDigits,
        maximumFractionDigits
    }).format(amount);
}


const formatQty = function(qty) {
    return Number(qty || 0).toFixed(4).replace(/(\.\d{2}\d*?)0+$/, '$1');
}

const parseNum = function(val, decimals = 4) {
    return parseFloat(parseFloat(val || 0).toFixed(decimals));
}


const splitDateTime = function(dateTime) {
    if (!dateTime) {
        return { date: '-', time: '-' };
    }

    const parts = dateTime.split(' ');
    return {
        date: parts.slice(0, 1).join(' '),
        time: parts.slice(1).join(' ')
    };
}

/**
 * Build Select2-compatible options array
 */
const extractSelect2OptionValue = function (item, key) {
    
    if (!key) return undefined;

    return key.split(".").reduce((acc, k) => {
        return acc && acc[k] !== undefined ? acc[k] : undefined;
    }, item);
};

const buildSelect2Options = function (data = [], config = {}) {

    if (!Array.isArray(data)) return [];

    const {
        idKey = "id",
        textKey = "name",
        
        idJoin = "_",
        textJoin = " - ",
        
        disabledKey = null,
        placeholder = null
    } = config;

    const result = [];

    // Placeholder
    if (placeholder) {
        result.push({
            id: "",
            text: placeholder,
            disabled: true
        });
    }

    data.forEach(item => {
        if (!item || typeof item !== "object") return;

        // ---- Resolve ID (single or multiple keys)
        const idValue = Array.isArray(idKey)
            ? idKey
                .map(key => extractSelect2OptionValue(item, key))
                .filter(val => val !== undefined && val !== null && val !== "")
                .join(idJoin)
            : extractSelect2OptionValue(item, idKey);

        // ---- Resolve TEXT (single or multiple keys)
        const textValue = Array.isArray(textKey)
            ? textKey
                .map(key => extractSelect2OptionValue(item, key))
                .filter(val => val !== undefined && val !== null && val !== "")
                .join(textJoin)
            : extractSelect2OptionValue(item, textKey);

        if (idValue === undefined || textValue === undefined) return;

        const option = {
            id: idValue,
            text: textValue
        };

        if (disabledKey) {
            option.disabled = Boolean(
                extractSelect2OptionValue(item, disabledKey)
            );
        }

        result.push(option);
    });

    return result;
};

/*
const formDataToObject = function(formData) {

    const obj = {};
    for (const [key, value] of formData.entries()) {
        const keys = key.match(/[^[\]]+/g); // extract nested keys
        let ref = obj;

        keys.forEach((k, index) => {
            if (index === keys.length - 1) {
                ref[k] = value;
            } else {
                ref[k] = ref[k] || {};
                ref = ref[k];
            }
        });
    }

    return obj;
}
*/

/**
 * Read files from a file input as base64-encoded objects.
 * Consistent with the product form pattern (Dropzone dataURL → base64 content).
 * Usage: const files = await readFilesAsBase64(document.getElementById('myInput'));
 * Returns: [{ name, mime_type, content }]  — content is raw base64 without the data URI prefix.
 */
const readFilesAsBase64 = function(fileInput) {
    const files = Array.from(fileInput?.files || []);
    return Promise.all(files.map(file => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload  = (e) => resolve({
            name:      file.name,
            mime_type: file.type,
            content:   e.target.result.split(',')[1], // strip "data:...;base64," prefix
        });
        reader.onerror = reject;
        reader.readAsDataURL(file);
    })));
};


/**
 * Read files from a Dropzone instance as base64-encoded objects.
 * Skips mock "existing" files pre-populated via populateDropzoneImage.
 * Usage: const files = await readDropzoneFilesAsBase64(getDropzoneInstance('#myDz'));
 * Returns: [{ name, mime_type, content }]  — content is raw base64 without the data URI prefix.
 */
const readDropzoneFilesAsBase64 = function(dropzoneInstance) {
    const files = (dropzoneInstance?.files || []).filter(f => !f.existing);
    return Promise.all(files.map(file => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload  = (e) => resolve({
            name:      file.name,
            mime_type: file.type,
            content:   e.target.result.split(',')[1],
        });
        reader.onerror = reject;
        reader.readAsDataURL(file);
    })));
};


/**
 * Download a protected attachment via credentialed fetch request.
 * Shows notyf.error if the file is not found or the request fails.
 * Usage: downloadAttachment('/attachments/5', 'invoice.pdf');
 */
const downloadAttachment = async function(url, filename) {
    try {
        const res = await fetch(url, { credentials: 'include' });
        if (res.status === 404) {
            notyf.error('File not found or has been deleted.');
            return;
        }
        if (!res.ok) {
            notyf.error('Failed to download file.');
            return;
        }
        const blob = await res.blob();
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(a.href);
    } catch (e) {
        notyf.error('Failed to download file.');
    }
};


const formDataToObject = function (formData) {
    const obj = {};

    for (const [fullKey, value] of formData.entries()) {
        const isArrayKey = fullKey.endsWith('[]');
        const cleanKey = isArrayKey ? fullKey.slice(0, -2) : fullKey;

        const keys = cleanKey.match(/[^[\]]+/g);
        let ref = obj;

        keys.forEach((k, index) => {
            const isLast = index === keys.length - 1;

            if (isLast) {
                if (isArrayKey) {
                    // Force array only when [] is present
                    if (!Array.isArray(ref[k])) {
                        ref[k] = [];
                    }
                    ref[k].push(value);
                } else {
                    // Scalar value (intentional overwrite)
                    ref[k] = value;
                }
            } else {
                if (!ref[k] || typeof ref[k] !== 'object') {
                    ref[k] = {};
                }
                ref = ref[k];
            }
        });
    }

    return obj;
};

const getContrastTextColor = function(bgColor) {
    
    // Remove #
    const hex = bgColor.replace('#', '');

    // Convert to RGB
    const r = parseInt(hex.substr(0, 2), 16) / 255;
    const g = parseInt(hex.substr(2, 2), 16) / 255;
    const b = parseInt(hex.substr(4, 2), 16) / 255;

    // Apply sRGB transformation
    const toLinear = (c) => {
        return (c <= 0.03928)
            ? c / 12.92
            : Math.pow((c + 0.055) / 1.055, 2.4);
    };

    const R = toLinear(r);
    const G = toLinear(g);
    const B = toLinear(b);

    // Calculate luminance
    const luminance = 0.2126 * R + 0.7152 * G + 0.0722 * B;

    // Return contrast color
    return luminance > 0.5 ? '#000000' : '#FFFFFF';
}
/**
 * True when rich-text HTML has no visible text (empty Jodit editors
 * produce markup like "<p><br></p>").
 */
const isHtmlEmpty = function(html) {
    if (!html) return true;
    return String(html).replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').trim() === '';
};
