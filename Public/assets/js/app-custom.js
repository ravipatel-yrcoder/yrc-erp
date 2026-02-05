const UNAUTHORIZED_MESSAGE = "Your session has expired. Please sign in again";
const DELETE_CONFIRM_MESSAGE = "Are you sure you want to delete this item?";

// Global instance for Notyf - Notification
window.notyf = new Notyf({
    duration: 2000,
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

                for(const [key, value] of Object.entries(errors)) {
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

    _input = input;

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
        const inputName = (input.name || '').replace(/\s+/g, '-').toLowerCase();
            

        const feedbackDivId = inputName ? `${inputName}-feedback` : 'feedback';

        // Find or create feedback div
        let feedback = input.parentElement.querySelector(`#${feedbackDivId}`);
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.id = feedbackDivId;
            feedback.className = type === 'error' ? 'invalid-feedback' : 'valid-feedback';
            input.parentElement.appendChild(feedback);
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
	

    Swal.fire({
        html: message,
        icon: type,
        buttonsStyling: false,
		showCancelButton: showCancelBtn,
        confirmButtonText: confirmBtnText,
		cancelButtonText: cancelBtnText,
		width: width,
		customClass: { 
			confirmButton: "btn "+confirmBtnClass,
			cancelButton: "btn "+cancelBtnClass,
            htmlContainer: htmlContainer,
            popup: 'app-swal-confirmation',
		}
    }).then((result) => {
        
		if (result.isConfirmed) {
            
			// Execute the callback function if provided
            if (confirmCallback && typeof confirmCallback === 'function') {
                confirmCallback();
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

    // destry if aready initiated
    const select2El = jQuery(selector);
    if (select2El.data("select2")) {

        select2El.off("change.select2Custom"); // remove custom change handler
        select2El.empty().select2("destroy");
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

const formatMySqlDate = function (date, format = null, fallback = '-') {

    if (!date) return fallback;

    try {
        const sysDateFormat = window.sysDefaultConfig.dateFormat;
        const sysDateTimeFormat = window.sysDefaultConfig?.dateTimeFormat;

        let parsedDate = null;
        let hasTime = false;

        // MySQL DATETIME: YYYY-MM-DD HH:MM:SS
        if (/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/.test(date)) {
            parsedDate = new Date(date.replace(' ', 'T'));
            hasTime = true;
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
        const outputFormat =
            format ||
            (hasTime ? sysDateTimeFormat : sysDateFormat);

        return flatpickr.formatDate(parsedDate, outputFormat);

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
    return Number(qty || 0).toFixed(2);
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
