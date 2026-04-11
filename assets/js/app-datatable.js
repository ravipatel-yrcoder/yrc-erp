'use strict';

const initDataTable = function(selector, userOptions={}) {
    
    const defaultOptions = {
        processing: true,
        serverSide: true,
        ordering: true,
        pageLength: 50,
        layout: {
            topStart: {
                rowClass: 'row mx-3 my-0 justify-content-between',
                features: [
                    { search: { placeholder: 'Search...', text: '_INPUT_' } }
                ],
            },
            topEnd: {},
            bottomStart: {
                rowClass: 'row mx-3 justify-content-between',
                features: [
                    {
                        pageLength: {
                            menu: [10, 25, 50, 100],
                            text: 'Show_MENU_'
                        },
                        info: true,
                    }
                ]
            },
            bottomEnd: {
                paging: {
                    firstLast: false
                }
            }
        },
        language: {
            paginate: {
                next: '<i class="icon-base bx bx-chevron-right scaleX-n1-rtl icon-sm"></i>',
                previous: '<i class="icon-base bx bx-chevron-left scaleX-n1-rtl icon-sm"></i>'
            },
            info: "Showing _START_ to _END_ of _TOTAL_",
            infoEmpty: "No records found",
            infoFiltered: "(filtered from _MAX_ records)"
        }
    };

    // Merge defaults with custom user options
    const finalOptions = jQuery.extend(true, {}, defaultOptions, userOptions);

    // Initialize and return the DataTable instance
    return new DataTable(document.querySelector(selector), finalOptions);
}

const mapApiToDataTable = function(json) {

    const apiResponseData = json.data || {};

    json.draw = apiResponseData.draw ?? 0;
    json.recordsTotal = apiResponseData.recordsTotal ?? 0;
    json.recordsFiltered = apiResponseData.recordsFiltered ?? 0;

    // Delete it unrelated data from json
    Object.keys(json).forEach(key => {
        if (!['draw', 'recordsTotal', 'recordsFiltered'].includes(key)) {
            delete json[key];
        }
    });

    return apiResponseData.data ?? [];
}