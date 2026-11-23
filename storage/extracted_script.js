
        $(document).ready(function () {
            const activeBanksData = [];
            const activePropertiesData = [];
            const activeProjectSitesData = [];
            const activeTransactionTypesData = [];

            function showToast(message, type = 'success') {
                if (typeof window.showToast === 'function') {
                    window.showToast(message, type);
                } else {
                    alert(message);
                }
            }

            function escapeHtml(text) {
                if (!text) return '';
                return $('<div>').text(text).html();
            }

            function getTodayString() {
                const d = new Date();
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            // Task 5: Dynamic Transaction Type Inputs for the chosen category
            function populateTransactionTypeFields(category) {
                const container = $('#dynamicTransactionTypesContainer');
                const wrapper = $('#txnTypeFieldsWrapper');
                const label = $('#txnCategoryLabel');
                wrapper.empty();

                if (!category) {
                    container.addClass('d-none');
                    return;
                }

                // Match category flexibly (e.g. Bank vs Bank Valuation)
                const targetCat = (category === 'Bank Valuation' || category === 'Bank') ? 'bank' : category.toLowerCase().trim();

                const matchedTypes = activeTransactionTypesData.filter(function (t) {
                    const tCat = (t.category || '').toLowerCase().trim();
                    if (targetCat === 'bank') {
                        return tCat === 'bank' || tCat === 'bank valuation';
                    }
                    return tCat === targetCat;
                });

                if (matchedTypes.length > 0) {
                    label.text(category);
                    matchedTypes.forEach(function (t) {
                        const fieldHtml = `
                                                            <div class="col-12 col-md-6">
                                                                <label class="form-label small fw-semibold">
                                                                    ${escapeHtml(t.name_en)}
                                                                    <span class="badge bg-secondary-subtle text-secondary fw-normal ms-1" style="font-size: 0.68rem;">Input</span>
                                                                </label>
                                                                <input type="text" class="form-control form-control-sm transaction-type-input"
                                                                    name="transaction_types[${t.id}]"
                                                                    data-id="${t.id}"
                                                                    data-name="${escapeHtml(t.name_en)}"
                                                                    placeholder="Enter ${escapeHtml(t.name_en)} details...">
                                                            </div>
                                                        `;
                        wrapper.append(fieldHtml);
                    });
                    container.removeClass('d-none');
                } else {
                    container.addClass('d-none');
                }
            }
            // Source / Category options based on Entry Flow Type
            const sourceOptionsByFlow = {
                income: [
                    { value: 'Construction', text: 'Construction (கட்டுமானம் - Stage/Advance)' },
                    { value: 'Bank Valuation', text: 'Bank (Valuation / Inspection fee)' },
                    { value: 'Rental', text: 'Rental (வாடகை வருமானம்)' },
                    { value: 'Office and other expenses', text: 'Office and other expenses' }
                ],
                expense: [
                    { value: 'Construction', text: 'Construction (கட்டுமானம் - Stage/Advance)' },
                    { value: 'Supplier', text: 'Supplier (சப்ளையர்)' },
                    { value: 'Office and other expenses', text: 'Office and other expenses' }
                ]
            };

            function syncSourceOptions(preferredValue) {
                const flow = $('#newLedgerFlow').val() || 'income';
                const options = sourceOptionsByFlow[flow] || sourceOptionsByFlow.income;
                const currentVal = preferredValue !== undefined ? preferredValue : $('#newLedgerSource').val();

                const select = $('#newLedgerSource');
                select.empty();

                let isCurrentValPresent = false;
                options.forEach(function (opt) {
                    const isSelected = (opt.value === currentVal);
                    if (isSelected) {
                        isCurrentValPresent = true;
                    }
                    select.append($('<option>', {
                        value: opt.value,
                        text: opt.text,
                        selected: isSelected
                    }));
                });

                if (!isCurrentValPresent && options.length > 0) {
                    select.val(options[0].value);
                }

                select.trigger('change');
            }

            // Entry Flow Type Change handler
            $('#newLedgerFlow').on('change', function () {
                syncSourceOptions();
            });

            // Category Change handler
            $('#newLedgerSource').on('change', function () {
                const val = $(this).val();
                $("#newLedgerAmount").val('');

                // Reset sub-groups
                $('.bank-fields-group').addClass('d-none');
                $('.rental-fields-group').addClass('d-none');
                $('.construction-fields-group').addClass('d-none');
                $('.supplier-fields-group').addClass('d-none');
                $('.office-expense-fields-group').addClass('d-none');

                if (val === 'Bank Valuation') {
                    $('.bank-fields-group').removeClass('d-none');
                    if (!$('#ledgerBankSelect').val()) {
                        $('#branchSelectContainer').addClass('d-none');
                    }
                } else if (val === 'Rental') {
                    $('.rental-fields-group').removeClass('d-none');
                    if (!$('#ledgerPropertySelect').val()) {
                        $('#propertyTypeSelectContainer').addClass('d-none');
                    }
                } else if (val === 'Construction') {
                    // Task 3: Show Construction site & client container
                    $('.construction-fields-group').removeClass('d-none');
                } else if (val === 'Supplier') {
                    // Task 4: Only append supplier select box, no site and no PO
                    $('.supplier-fields-group').removeClass('d-none');
                } else if (val === 'Office and other expenses') {
                    $('.office-expense-fields-group').removeClass('d-none');
                }

                // Task 5: Populate dynamic transaction type inputs for this category
                populateTransactionTypeFields(val);
                                syncCashReceivedFromVisibility();
            });

            function syncCashReceivedFromVisibility() {
                const shouldShow = $('#newLedgerFlow').val() === 'income'
                    && $('#newLedgerSource').val() === 'Construction'
                    && Boolean($('#constructionSite').val());

                $('#cashReceivedFromContainer').toggleClass('d-none', !shouldShow);
                if (!shouldShow) {
                    $('input[name="cash_received_from"]').prop('checked', false).removeClass('is-invalid');
                    $('#errCashReceivedFrom').text('').hide();
                }
            }

            // Task 3: Construction Site Change -> Append Client Name input with auto-filled value
            $('#constructionSite').on('change', function () {
                $("#newLedgerAmount").val('');
                const siteId = $(this).val();
                const selectedOpt = $(this).find('option:selected');
                const clientName = selectedOpt.data('client-name') || '';
                const siteName = selectedOpt.data('site-name') || '';

                if (siteId) {
                    $('#constructionClientName').val(clientName);
                    $('#constructionClientContainer').removeClass('d-none');
                    if (siteName && (!$('#newLedgerDesc').val() || $('#newLedgerDesc').data('autofilled') === 'site')) {
                        $('#newLedgerDesc').val(`Construction: ${siteName}${clientName ? ' (' + clientName + ')' : ''}`).data('autofilled', 'site');
                    }
                } else {
                    $('#constructionClientContainer').addClass('d-none');
                    $('#constructionClientName').val('');
                }
                syncCashReceivedFromVisibility();
                checkPainterElectricianWages();
            });

            let currentWageCheckData = null;

            function checkPainterElectricianWages() {
                const source = $('#newLedgerSource').val();
                const flow = $('#newLedgerFlow').val();
                const siteId = $('#constructionSite').val();
                const entryDate = $('#newLedgerDate').val();

                if (source !== 'Construction' || flow !== 'expense' || !siteId || !entryDate) {
                    $('#wageCheckCard').addClass('d-none');
                    currentWageCheckData = null;
                    $('#ledgerDailyAllocationId').val('');
                    $('#ledgerIsAmWagePayment').val('0');
                    return;
                }

                $.ajax({
                    url: ""blade"",
                    type: 'GET',
                    data: {
                        project_site_id: siteId,
                        entry_date: entryDate
                    },
                    success: function (res) {
                        if (res.success && res.found && res.has_matching_trades && res.items.length > 0 && !res.all_paid && res.unpaid_total > 0) {
                            currentWageCheckData = res;
                            $('#ledgerDailyAllocationId').val(res.allocation_id);

                            let itemsHtml = '';
                            res.items.forEach(function (it) {
                                const isPaint = (it.name_en || '').toLowerCase().includes('paint');
                                const icon = isPaint ? 'bi-brush' : 'bi-lightning-charge';
                                const iconColor = isPaint ? 'text-info' : 'text-warning';
                                const statusHtml = it.is_paid
                                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.68rem;"><i class="bi bi-check2"></i> Paid</span>'
                                    : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:0.68rem;">Pending</span>';

                                itemsHtml += `
                                    <div class="px-2 py-1 rounded-2 border bg-light d-inline-flex align-items-center gap-2" style="font-size: 0.8rem;">
                                        <i class="bi ${icon} ${iconColor}"></i>
                                        <span class="fw-semibold text-dark">${it.name_ta || it.name_en}</span>
                                        <span class="text-muted" style="font-size: 0.72rem;">(${it.count} ஆட்கள்)</span>
                                        <strong class="text-success ms-1">₹${Math.round(it.subtotal).toLocaleString('en-IN')}</strong>
                                        ${statusHtml}
                                    </div>
                                `;
                            });
                            $('#wageCheckItemsContainer').html(itemsHtml);

                            $('#wageCheckBadge').removeClass('bg-success-subtle text-success').addClass('bg-primary-subtle text-primary').text(`${res.items.length} Trade(s) Pending`);
                            $('#wageCheckCard').removeClass('d-none');
                            syncWageTotalsAndAutoFill();
                        } else {
                            // Once completed/settled or no unpaid trades, do NOT auto-fill and clear wage payment flags!
                            $('#wageCheckCard').addClass('d-none');
                            currentWageCheckData = null;
                            $('#ledgerDailyAllocationId').val('');
                            $('#ledgerIsAmWagePayment').val('0');
                        }
                    },
                    error: function () {
                        $('#wageCheckCard').addClass('d-none');
                        currentWageCheckData = null;
                        $('#ledgerDailyAllocationId').val('');
                        $('#ledgerIsAmWagePayment').val('0');
                    }
                });
            }

            function reindexExtraWorkers() {
                $('#extraWorkersContainer .extra-worker-item').each(function (idx) {
                    $(this).find('.extra-worker-name').attr('name', `extra_workers[${idx}][name]`);
                    $(this).find('.extra-worker-work').attr('name', `extra_workers[${idx}][work]`);
                    $(this).find('.extra-worker-salary').attr('name', `extra_workers[${idx}][salary]`);
                });
            }

            function syncWageTotalsAndAutoFill() {
                if (!currentWageCheckData || currentWageCheckData.unpaid_total <= 0) return;

                const baseUnpaid = parseFloat(currentWageCheckData.unpaid_total) || 0;
                let extraTotal = 0;
                const extraDetails = [];

                $('#extraWorkersContainer .extra-worker-item').each(function () {
                    const name = $(this).find('.extra-worker-name').val().trim();
                    const work = $(this).find('.extra-worker-work').val().trim();
                    const salary = parseFloat($(this).find('.extra-worker-salary').val()) || 0;
                    if (salary > 0 || name) {
                        extraTotal += salary;
                        const roleStr = work ? ` (${work})` : '';
                        extraDetails.push(`${name || 'Helper'}${roleStr}: ₹${Math.round(salary).toLocaleString('en-IN')}`);
                    }
                });

                const grandTotal = Math.round(baseUnpaid + extraTotal);
                $('#wageCheckTotalText').text(`₹${grandTotal.toLocaleString('en-IN')}`);

                const siteName = $('#constructionSite option:selected').data('site-name') || 'Site';
                const dateVal = $('#newLedgerDate').val();
                const cashSourceVal = $('input[name="wage_cash_source"]:checked').val() || 'office_cash';
                const cashSourceText = cashSourceVal === 'project_cash' ? 'Project Cash (திட்ட ரொக்கம்)' : 'Office Cash (அலுவலக ரொக்கம்)';

                let extraText = '';
                if (extraDetails.length > 0) {
                    extraText = ` + Extra: ${extraDetails.join(', ')}`;
                }

                $('#newLedgerAmount').val(grandTotal);
                $('#newLedgerDesc').val(`Painter & Electrician daily wages (${dateVal})`);
                $('#ledgerIsAmWagePayment').val('1');
                $('#ledgerDailyAllocationId').val(currentWageCheckData.allocation_id);
            }

            // Add Extra Worker Row (Clean, compact layout)
            $('#btnAddExtraWorker').on('click', function () {
                const rowHtml = `
                    <div class="extra-worker-item p-2 rounded-2 bg-light border">
                        <div class="row g-1 align-items-center">
                            <div class="col-12 col-sm-4">
                                <input type="text" class="form-control form-control-sm extra-worker-name bg-white" placeholder="Worker Name (பெயர்)" required>
                            </div>
                            <div class="col-12 col-sm-4">
                                <input type="text" class="form-control form-control-sm extra-worker-work bg-white" placeholder="Role (வேலை / பணி)" required>
                            </div>
                            <div class="col-9 col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">₹</span>
                                    <input type="number" step="1" min="0" class="form-control form-control-sm extra-worker-salary bg-white" placeholder="Salary" required>
                                </div>
                            </div>
                            <div class="col-3 col-sm-1 text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-extra-worker" title="Remove">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                $('#extraWorkersContainer').append(rowHtml);
                reindexExtraWorkers();
                syncWageTotalsAndAutoFill();
            });

            $(document).on('click', '.btn-remove-extra-worker', function () {
                $(this).closest('.extra-worker-item').remove();
                reindexExtraWorkers();
                syncWageTotalsAndAutoFill();
            });

            $(document).on('input change', '.extra-worker-name, .extra-worker-work, .extra-worker-salary', function () {
                syncWageTotalsAndAutoFill();
            });

            $(document).on('change', 'input[name="wage_cash_source"]', function () {
                syncWageTotalsAndAutoFill();
            });

            $('#newLedgerDate, #newLedgerFlow').on('change', function () {
                checkPainterElectricianWages();
            });

            // Task 4: Supplier Selection -> Suggest description
            $('#ledgerSupplier').on('change', function () {
                $("#newLedgerAmount").val('');
                const selectedOpt = $(this).find('option:selected');
                const supplierName = selectedOpt.data('supplier-name') || selectedOpt.text();
                if (supplierName && (!$('#newLedgerDesc').val() || $('#newLedgerDesc').data('autofilled') === 'supplier')) {
                    $('#newLedgerDesc').val(`Payment to ${supplierName}`).data('autofilled', 'supplier');
                }
            });

            // Bank Selection -> Show active branches
            $('#ledgerBankSelect').on('change', function () {
                $("#newLedgerAmount").val('');
                const bankId = parseInt($(this).val(), 10);
                const branchSelect = $('#ledgerBranchSelect');
                branchSelect.empty();

                if (!bankId) {
                    $('#branchSelectContainer').addClass('d-none');
                    branchSelect.append('<option value="" disabled selected>-- Select Branch (முதலில் கிளையை தேர்வு செய்க) --</option>');
                    return;
                }

                const selectedBank = activeBanksData.find(b => b.id === bankId);
                const activeBranches = (selectedBank && selectedBank.branches)
                    ? selectedBank.branches.filter(br => br.status === 'Active')
                    : [];

                if (activeBranches.length > 0) {
                    branchSelect.append('<option value="" disabled selected>-- Select Branch (கிளையை தேர்வு செய்க) --</option>');
                    activeBranches.forEach(br => {
                        const taText = br.branch_name_ta ? ` (${br.branch_name_ta})` : '';
                        const feeVal = parseFloat(br.valuation_fee) || 0;
                        const feeText = feeVal > 0 ? ` - ₹${feeVal.toLocaleString('en-IN')}` : '';
                        branchSelect.append(
                            $('<option></option>')
                                .val(br.id)
                                .attr('data-branch-name', br.branch_name)
                                .attr('data-bank-name', selectedBank.bank_name)
                                .attr('data-fee', feeVal)
                                .text(`${br.branch_name}${taText}${feeText}`)
                        );
                    });
                    $('#branchSelectContainer').removeClass('d-none');
                } else {
                    branchSelect.append('<option value="" disabled selected>-- No active branches for this bank (செயலில் உள்ள கிளைகள் இல்லை) --</option>');
                    $('#branchSelectContainer').removeClass('d-none');
                }
            });

            // Branch selection -> auto-suggest valuation fee
            $('#ledgerBranchSelect').on('change', function () {
                $("#newLedgerAmount").val('');
                const selected = $(this).find(':selected');
                const fee = selected.data('fee');
                if (fee && (!$('#newLedgerAmount').val() || $('#newLedgerAmount').data('autofilled') === 'bank')) {
                    $('#newLedgerAmount').val(fee).data('autofilled', 'bank');
                }
            });

            // Property Selection -> Show property types/units
            $('#ledgerPropertySelect').on('change', function () {
                $("#newLedgerAmount").val('');
                const propertyId = parseInt($(this).val(), 10);
                const propertyTypeSelect = $('#ledgerPropertyTypeSelect');
                propertyTypeSelect.empty();

                if (!propertyId) {
                    $('#propertyTypeSelectContainer').addClass('d-none');
                    propertyTypeSelect.append('<option value="" disabled selected>-- Select Property Type / Unit (அலகு / வகை தேர்வு செய்க) --</option>');
                    return;
                }

                const selectedProperty = activePropertiesData.find(p => p.id === propertyId);
                const activePropertyTypes = (selectedProperty && selectedProperty.property_types)
                    ? selectedProperty.property_types.filter(pt => pt.status === 'Active')
                    : [];

                if (activePropertyTypes.length > 0) {
                    propertyTypeSelect.append('<option value="" disabled selected>-- Select Property Type / Unit (அலகு / வகை தேர்வு செய்க) --</option>');
                    activePropertyTypes.forEach(pt => {
                        const rentVal = parseFloat(pt.monthly_rent) || 0;
                        const rentText = rentVal > 0 ? ` - ₹${rentVal.toLocaleString('en-IN')}/mo` : '';
                        const tenantText = pt.tenant_name ? ` (Tenant: ${pt.tenant_name})` : '';
                        const label = `${pt.shop_home_office_name} [${pt.property_type}]${tenantText}${rentText}`;
                        propertyTypeSelect.append(
                            $('<option></option>')
                                .val(pt.id)
                                .attr('data-unit-name', pt.shop_home_office_name)
                                .attr('data-prop-type', pt.property_type)
                                .attr('data-property-name', selectedProperty.property_name)
                                .attr('data-tenant', pt.tenant_name || '')
                                .attr('data-rent', rentVal)
                                .text(label)
                        );
                    });
                    $('#propertyTypeSelectContainer').removeClass('d-none');
                } else {
                    propertyTypeSelect.append('<option value="" disabled selected>-- No active units/types for this property (செயலில் உள்ள பிரிவுகள் இல்லை) --</option>');
                    $('#propertyTypeSelectContainer').removeClass('d-none');
                }
            });

            // Property type selection -> auto-suggest monthly rent
            $('#ledgerPropertyTypeSelect').on('change', function () {
                $("#newLedgerAmount").val('');
                const selected = $(this).find(':selected');
                const rent = selected.data('rent');
                if (rent && (!$('#newLedgerAmount').val() || $('#newLedgerAmount').data('autofilled') === 'rental')) {
                    $('#newLedgerAmount').val(rent).data('autofilled', 'rental');
                }
            });

            $('#newLedgerAmount').on('input', function () {
                $(this).removeData('autofilled');
            });

            // Image preview
            $('#newLedgerImage').on('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        showToast('Image file size must be less than 5MB.', 'warning');
                        $(this).val('');
                        $('#newLedgerImagePreviewBox').attr('style', 'display: none !important;').removeClass('d-flex');
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function (evt) {
                        $('#newLedgerImagePreviewImg').attr('src', evt.target.result);
                        $('#newLedgerImageName').text(file.name);
                        $('#newLedgerImageSize').text((file.size / 1024).toFixed(1) + ' KB');
                        $('#newLedgerImagePreviewBox').removeAttr('style').addClass('d-flex');
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#newLedgerImagePreviewBox').attr('style', 'display: none !important;').removeClass('d-flex');
                }
            });

            $('#newLedgerImageClearBtn').on('click', function () {
                $('#newLedgerImage').val('');
                $('#newLedgerImagePreviewBox').attr('style', 'display: none !important;').removeClass('d-flex');
            });

            function clearFormErrors() {
                $('#ledgerForm .is-invalid').removeClass('is-invalid');
                $('#ledgerForm .invalid-feedback').text('').hide();
            }

            function displayFormErrors(errors) {
                clearFormErrors();
                if (!errors) return;

                if (errors.entry_date) {
                    $('#newLedgerDate').addClass('is-invalid');
                    $('#errEntryDate').text(errors.entry_date[0]).show();
                }
                if (errors.flow) {
                    $('#newLedgerFlow').addClass('is-invalid');
                    $('#errFlow').text(errors.flow[0]).show();
                }
                if (errors.source) {
                    $('#newLedgerSource').addClass('is-invalid');
                    $('#errSource').text(errors.source[0]).show();
                }
                if (errors.amount) {
                    $('#newLedgerAmount').addClass('is-invalid');
                    $('#errAmount').text(errors.amount[0]).show();
                }
                if (errors.description || errors.desc) {
                    $('#newLedgerDesc').addClass('is-invalid');
                    $('#errDescription').text((errors.description || errors.desc)[0]).show();
                }
                if (errors.project_site_id) {
                    $('#constructionSite').addClass('is-invalid');
                    $('#errProjectSiteId').text(errors.project_site_id[0]).show();
                }
                if (errors.client_name) {
                    $('#constructionClientName').addClass('is-invalid');
                    $('#errClientName').text(errors.client_name[0]).show();
                }
                if (errors.cash_received_from) {
                    $('input[name="cash_received_from"]').addClass('is-invalid');
                    $('#errCashReceivedFrom').text(errors.cash_received_from[0]).show();
                }
                if (errors.supplier_id) {
                    $('#ledgerSupplier').addClass('is-invalid');
                    $('#errSupplierId').text(errors.supplier_id[0]).show();
                }
                if (errors.bank_id) {
                    $('#ledgerBankSelect').addClass('is-invalid');
                    $('#errBankId').text(errors.bank_id[0]).show();
                }
                if (errors.branch_id) {
                    $('#ledgerBranchSelect').addClass('is-invalid');
                    $('#errBranchId').text(errors.branch_id[0]).show();
                }
                if (errors.bank_customer_ref) {
                    $('#bankCustomer').addClass('is-invalid');
                    $('#errBankCustomerRef').text(errors.bank_customer_ref[0]).show();
                }
                if (errors.property_id) {
                    $('#ledgerPropertySelect').addClass('is-invalid');
                    $('#errPropertyId').text(errors.property_id[0]).show();
                }
                if (errors.property_type_id) {
                    $('#ledgerPropertyTypeSelect').addClass('is-invalid');
                    $('#errPropertyTypeId').text(errors.property_type_id[0]).show();
                }
                if (errors.office_expense_details) {
                    $('#officeExpenseDetails').addClass('is-invalid');
                    $('#errOfficeExpenseDetails').text(errors.office_expense_details[0]).show();
                }
                if (errors.receipt_image) {
                    $('#newLedgerImage').addClass('is-invalid');
                    $('#errReceiptImage').text(errors.receipt_image[0]).show();
                }
            }

            $('#ledgerForm input, #ledgerForm select').on('input change', function () {
                $(this).removeClass('is-invalid');
            });

            function resetLedgerModalForm() {
                $('#ledgerForm')[0].reset();
                clearFormErrors();
                $('#newLedgerDate').val(getTodayString());
                $('#constructionSite').val('');
                $('#constructionClientContainer').addClass('d-none');
                $('#constructionClientName').val('');
                $('input[name="cash_received_from"]').prop('checked', false);
                $('#cashReceivedFromContainer').addClass('d-none');
                $('#wageCheckCard').addClass('d-none');
                $('#wageCheckItemsContainer').empty();
                $('#extraWorkersContainer').empty();
                $('#ledgerDailyAllocationId').val('');
                $('#ledgerIsAmWagePayment').val('0');
                $('input[name="wage_cash_source"][value="office_cash"]').prop('checked', true);
                currentWageCheckData = null;
                $('#ledgerSupplier').val('');
                $('#ledgerBankSelect').val('');
                $('#ledgerBranchSelect').empty().append('<option value="" disabled selected>-- Select Branch --</option>');
                $('#branchSelectContainer').addClass('d-none');
                $('#bankCustomer').val('');
                $('#ledgerPropertySelect').val('');
                $('#ledgerPropertyTypeSelect').empty().append('<option value="" disabled selected>-- Select Property Type / Unit --</option>');
                $('#propertyTypeSelectContainer').addClass('d-none');
                $('#newLedgerAmount').removeData('autofilled');
                $('#newLedgerDesc').removeData('autofilled');
                $('#newLedgerImage').val('');
                $('#newLedgerImagePreviewBox').attr('style', 'display: none !important;').removeClass('d-flex');
                $('#dynamicTransactionTypesContainer').addClass('d-none');
                $('#txnTypeFieldsWrapper').empty();
                syncSourceOptions('Construction');
            }

            // Submit Form via AJAX
            $('#ledgerForm').on('submit', function (e) {
                e.preventDefault();
                clearFormErrors();
                const form = this;
                const formData = new FormData(form);

                $('#btnSaveLedgerEntry').prop('disabled', true);
                $('#saveSpinner').removeClass('d-none');

                $.ajax({
                    url: ""blade"",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        $('#btnSaveLedgerEntry').prop('disabled', false);
                        $('#saveSpinner').addClass('d-none');

                        if (res.success) {
                            showToast(res.message || 'Ledger entry recorded successfully!', 'success');
                            $('#ledgerModal').modal('hide');
                            resetLedgerModalForm();
                            setTimeout(function () {
                                window.location.reload();
                            }, 500);
                        } else {
                            showToast(res.message || 'Failed to save entry', 'danger');
                        }
                    },
                    error: function (xhr) {
                        $('#btnSaveLedgerEntry').prop('disabled', false);
                        $('#saveSpinner').addClass('d-none');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayFormErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred while saving the ledger entry.';
                            showToast(errorMsg, 'danger');
                        }
                    }
                });
            });

            let currentPage = 1;
            let perPage = parseInt($('#perPageSelect').val()) || 10;
            const todayStr = '"blade"';
            const yesterdayStr = '"blade"';

            function formatMoney(amount) {
                return parseFloat(amount || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function calculateDateSummary(selectedDate) {
                let opening = 0;
                let dayInc = 0;
                let dayExp = 0;

                $('#ledgerTableBody tr[data-flow]').each(function () {
                    const rowDate = ($(this).data('date') || '').toString().trim();
                    const dr = parseFloat($(this).data('dr')) || 0;
                    const cr = parseFloat($(this).data('cr')) || 0;

                    if (rowDate < selectedDate) {
                        opening += (dr - cr);
                    } else if (rowDate === selectedDate) {
                        dayInc += dr;
                        dayExp += cr;
                    }
                });

                return {
                    opening: opening,
                    income: dayInc,
                    expense: dayExp,
                    closing: opening + dayInc - dayExp
                };
            }

            function updateSummaryCards() {
                const dateFilter = $('#ledgerDateFilter').val();
                const customDate = $('#ledgerCustomDateInput').val();

                if (dateFilter === 'all') {
                    $('#openingBalanceRow').hide();
                    let totalInc = 0;
                    let totalExp = 0;
                    $('#ledgerTableBody tr[data-flow]').each(function () {
                        totalInc += parseFloat($(this).data('dr')) || 0;
                        totalExp += parseFloat($(this).data('cr')) || 0;
                    });
                    const closing = totalInc - totalExp;

                    $('#lblLedgerIncome').text('Total Income (Dr)');
                    $('#lblLedgerIncomeSub').text('மொத்த வரவு');
                    $('#lblLedgerExpense').text('Total Expense (Cr)');
                    $('#lblLedgerExpenseSub').text('மொத்த செலவு');
                    $('#lblLedgerClosing').text('Closing Running Balance');
                    $('#lblLedgerClosingSub').text('கையிருப்பு பேலன்ஸ்');

                    $('#ledgerOpeningBal').text('₹0.00');
                    $('#ledgerTotalIncome').text(totalInc > 0 ? '+₹' + formatMoney(totalInc) : '₹0.00');
                    $('#ledgerTotalExpense').text(totalExp > 0 ? '−₹' + formatMoney(totalExp) : '₹0.00');
                    $('#ledgerClosingBal').text((closing >= 0 ? '₹' : '−₹') + formatMoney(Math.abs(closing)));
                } else {
                    const targetDate = (dateFilter === 'today') ? todayStr : ((dateFilter === 'yesterday') ? yesterdayStr : customDate);
                    const stats = calculateDateSummary(targetDate);

                    $('#openingBalanceRow').show();
                    $('#tableOpeningBalCell').text((stats.opening >= 0 ? '₹' : '−₹') + formatMoney(Math.abs(stats.opening)));

                    const dateLabel = (dateFilter === 'today') ? "Today's" : ((dateFilter === 'yesterday') ? "Yesterday's" : targetDate);
                    $('#lblLedgerIncome').text(`${dateLabel} Income (Dr)`);
                    $('#lblLedgerIncomeSub').text('இன்றைய வரவு');
                    $('#lblLedgerExpense').text(`${dateLabel} Expense (Cr)`);
                    $('#lblLedgerExpenseSub').text('இன்றைய செலவு');
                    $('#lblLedgerClosing').text(`${dateLabel} Closing Balance`);
                    $('#lblLedgerClosingSub').text('கையிருப்பு பேலன்ஸ்');

                    $('#ledgerOpeningBal').text((stats.opening >= 0 ? '₹' : '−₹') + formatMoney(Math.abs(stats.opening)));
                    $('#ledgerTotalIncome').text(stats.income > 0 ? '+₹' + formatMoney(stats.income) : '₹0.00');
                    $('#ledgerTotalExpense').text(stats.expense > 0 ? '−₹' + formatMoney(stats.expense) : '₹0.00');
                    $('#ledgerClosingBal').text((stats.closing >= 0 ? '₹' : '−₹') + formatMoney(Math.abs(stats.closing)));
                }
            }

            function getMatchingRows() {
                const query = ($('#ledgerSearchInput').val() || '').toLowerCase().trim();
                const selectedSite = ($('#siteFilter').val() || '').trim();
                const source = $('#sourceFilter').val();
                const flow = $('#flowFilter').val();
                const dateFilter = $('#ledgerDateFilter').val();
                const customDate = $('#ledgerCustomDateInput').val();
                const matching = [];

                $('#ledgerTableBody tr[data-flow]').each(function () {
                    const desc = ($(this).data('desc') || '').toString().toLowerCase();
                    const rowSource = ($(this).data('source') || '').toString();
                    const rowFlow = ($(this).data('flow') || '').toString();
                    const rowSite = ($(this).data('site') || '').toString().trim();
                    const rowDate = ($(this).data('date') || '').toString().trim();

                    const matchQuery = !query || desc.includes(query) || rowSource.toLowerCase().includes(query) || rowSite.toLowerCase().includes(query);
                    const matchSite = !selectedSite || (rowSite !== '-' && rowSite.toLowerCase() === selectedSite.toLowerCase());
                    const matchSource = !source || rowSource === source;
                    const matchFlow = !flow || rowFlow === flow;

                    let matchDate = true;
                    if (dateFilter === 'today') {
                        matchDate = (rowDate === todayStr);
                    } else if (dateFilter === 'yesterday') {
                        matchDate = (rowDate === yesterdayStr);
                    } else if (dateFilter === 'custom' && customDate) {
                        matchDate = (rowDate === customDate);
                    }

                    if (matchQuery && matchSite && matchSource && matchFlow && matchDate) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                updateSummaryCards();
                const matchingRows = getMatchingRows();
                const totalCount = $('#ledgerTableBody tr[data-flow]').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;
                const selectedSite = ($('#siteFilter').val() || '').trim();

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows and date summary rows initially
                $('#ledgerTableBody tr[data-flow]').hide();
                $('.date-summary-row').hide();

                if (totalCount === 0) {
                    $('#ledgerFilterEmptyRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#ledgerCountBadge').html('Showing <strong class="text-dark">0</strong> entries');
                    return;
                }

                if (filteredCount === 0) {
                    $('#ledgerFilterEmptyRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    let badgeHtml = `Showing <strong class="text-dark">0</strong> of <strong class="text-dark">${totalCount}</strong> entries`;
                    if (selectedSite) {
                        badgeHtml += ` <span class="badge bg-info-subtle text-info border border-info-subtle ms-1"><i class="bi bi-building me-1"></i>Site: ${escapeHtml(selectedSite)}</span>`;
                    }
                    $('#ledgerCountBadge').html(badgeHtml);
                    return;
                } else {
                    $('#ledgerFilterEmptyRow').addClass('d-none');
                    $('#tablePaginationContainer').removeClass('d-none');
                }

                // Slice visible rows for current page
                const startIndex = (currentPage - 1) * perPage;
                const endIndex = Math.min(startIndex + perPage, filteredCount);

                const visibleDates = new Set();
                for (let i = startIndex; i < endIndex; i++) {
                    const row = $(matchingRows[i]);
                    row.show();
                    const d = row.data('date');
                    if (d) {
                        visibleDates.add(d);
                    }
                }

                visibleDates.forEach(function (d) {
                    $(`.date-summary-row[data-summary-date="${d}"]`).show();
                });

                // Update summary footer text
                let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${filteredCount}</strong>`;
                if (filteredCount !== totalCount) {
                    summaryHtml += ` <span class="text-muted">(filtered from ${totalCount})</span>`;
                }
                summaryHtml += ' entries';
                $('#paginationSummary').html(summaryHtml);

                let badgeHtml = `Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> entries`;
                if (selectedSite) {
                    badgeHtml += ` <span class="badge bg-info-subtle text-info border border-info-subtle ms-1"><i class="bi bi-building me-1"></i>Site: ${escapeHtml(selectedSite)}</span>`;
                }
                $('#ledgerCountBadge').html(badgeHtml);

                renderPaginationButtons(totalPages, currentPage);
            }
                if (filteredCount !== totalCount) {
                    summaryHtml += ` <span class="text-muted">(filtered from ${totalCount})</span>`;
                }
                summaryHtml += ' entries';
                $('#paginationSummary').html(summaryHtml);

                let badgeHtml = `Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> entries`;
                if (selectedSite) {
                    badgeHtml += ` <span class="badge bg-info-subtle text-info border border-info-subtle ms-1"><i class="bi bi-building me-1"></i>Site: ${escapeHtml(selectedSite)}</span>`;
                }
                $('#ledgerCountBadge').html(badgeHtml);

                renderPaginationButtons(totalPages, currentPage);
            }

            function renderPaginationButtons(totalPages, current) {
                const nav = $('#paginationNav');
                nav.empty();

                const prevDisabled = current === 1 ? 'disabled' : '';
                nav.append(`
                                        <li class="page-item ${prevDisabled}">
                                            <button class="page-link" type="button" data-page="${current - 1}" aria-label="Previous" title="Previous Page">
                                                <i class="bi bi-chevron-left"></i>
                                            </button>
                                        </li>
                                    `);

                let pages = [];
                if (totalPages <= 7) {
                    for (let i = 1; i <= totalPages; i++) pages.push(i);
                } else {
                    pages.push(1);
                    if (current > 3) pages.push('...');
                    const start = Math.max(2, current - 1);
                    const end = Math.min(totalPages - 1, current + 1);
                    for (let i = start; i <= end; i++) {
                        if (!pages.includes(i)) pages.push(i);
                    }
                    if (current < totalPages - 2) pages.push('...');
                    if (!pages.includes(totalPages)) pages.push(totalPages);
                }

                pages.forEach(p => {
                    if (p === '...') {
                        nav.append(`<li class="page-item disabled"><span class="page-link border-0 bg-transparent">&hellip;</span></li>`);
                    } else {
                        const active = p === current ? 'active' : '';
                        nav.append(`
                                                <li class="page-item ${active}">
                                                    <button class="page-link" type="button" data-page="${p}">${p}</button>
                                                </li>
                                            `);
                    }
                });

                const nextDisabled = current === totalPages ? 'disabled' : '';
                nav.append(`
                                        <li class="page-item ${nextDisabled}">
                                            <button class="page-link" type="button" data-page="${current + 1}" aria-label="Next" title="Next Page">
                                                <i class="bi bi-chevron-right"></i>
                                            </button>
                                        </li>
                                    `);
            }

            // Per page dropdown change
            $('#perPageSelect').on('change', function () {
                perPage = parseInt($(this).val()) || 10;
                currentPage = 1;
                updatePagination();
            });

            // Pagination button click delegation
            $(document).on('click', '#paginationNav .page-link', function (e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                    currentPage = parseInt(page);
                    updatePagination();
                }
            });

            $('#ledgerDateFilter').on('change', function () {
                const val = $(this).val();
                if (val === 'custom') {
                    $('#ledgerCustomDateCol').removeClass('d-none');
                } else {
                    $('#ledgerCustomDateCol').addClass('d-none');
                }
                currentPage = 1;
                updatePagination();
            });

            $('#ledgerCustomDateInput').on('change input', function () {
                currentPage = 1;
                updatePagination();
            });

            $('#ledgerSearchInput, #siteFilter, #sourceFilter, #flowFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            $('#ledgerFilterResetBtn, #clearLedgerFiltersBtn').on('click', function () {
                $('#ledgerSearchInput').val('');
                $('#ledgerDateFilter').val('today');
                $('#ledgerCustomDateCol').addClass('d-none');
                $('#siteFilter').val('');
                $('#sourceFilter').val('');
                $('#flowFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            // Initial pagination run
            updatePagination();

            $('#ledgerModal').on('show.bs.modal', function () {
                clearFormErrors();
            });

            $('#ledgerModal').on('hidden.bs.modal', function () {
                resetLedgerModalForm();
            });

            // Initialize Page
            syncSourceOptions('Construction');
        });
    