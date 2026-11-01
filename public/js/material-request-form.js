(() => {
    const draftKey = 'MM_MATERIAL_REQUEST_DRAFT';
    const templateUrl = document.getElementById('request-page').dataset.templateUrl;
    const form = document.getElementById('material-request-form');
    const container = document.getElementById('material-request-items');
    const lineTemplate = document.getElementById('material-line-template');
    const status = document.getElementById('request-status');
    const optionNames = ['mtart', 'meins', 'purchasingGroup', 'matkl', 'bklas'];
    const referenceOptions = Object.fromEntries(optionNames.map(name => [name, []]));
    const columnFields = [
        'mtart', 'werks', 'lgort', 'maktx', 'meins', 'purchasingGroup', 'matkl', 'dismm',
        'mtvfp', 'sernp', 'prctr', 'bklas', 'vprsv', 'peinh', 'verpr', 'bwtty', 'longText',
    ];

    const parseOption = (value, group = '') => {
        const label = String(value ?? '').trim();
        const match = label.match(/^([^\s]+)/);
        if (!match) return null;
        return { value: match[1], label: group ? `${label} - ${group}` : label };
    };

    async function loadReferenceOptions() {
        if (typeof XLSX === 'undefined') throw new Error('Pustaka Excel tidak tersedia untuk memuat pilihan Form LPPM.');
        const response = await fetch(templateUrl);
        if (!response.ok) throw new Error('Template Form LPPM tidak dapat dibuka.');
        const workbook = XLSX.read(new Uint8Array(await response.arrayBuffer()), { type: 'array' });
        const sheet = workbook.Sheets.REFERENCE;
        if (!sheet) throw new Error('Sheet REFERENCE tidak ditemukan pada template.');

        const options = Object.fromEntries(optionNames.map(name => [name, new Map()]));
        XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '', blankrows: false }).slice(2).forEach(row => {
            const values = {
                mtart: parseOption(row[1]),
                meins: parseOption(row[4]),
                purchasingGroup: parseOption(row[7]),
                matkl: parseOption(row[10]),
                bklas: parseOption(row[14], row[13]),
            };
            Object.entries(values).forEach(([name, option]) => {
                if (option && !options[name].has(option.value)) options[name].set(option.value, option);
            });
        });
        Object.entries(options).forEach(([name, entries]) => {
            referenceOptions[name] = [...entries.values()];
        });
    }

    function updateNames() {
        container.querySelectorAll('.request-item').forEach((item, index) => {
            item.querySelector('[data-item-title]').textContent = `Material ${index + 1}`;
            item.querySelectorAll('[data-field]').forEach(input => {
                input.name = `materials[${index}][${input.dataset.field}]`;
            });
            item.querySelector('[data-remove-item]').disabled = container.children.length === 1;
        });
        document.getElementById('add-material-row').disabled = container.children.length >= 10;
    }

    function addMaterialLine(values = {}) {
        const fragment = lineTemplate.content.cloneNode(true);
        const line = fragment.querySelector('.request-item');
        line.querySelectorAll('[data-field]').forEach(input => {
            const name = input.dataset.field;
            if (referenceOptions[name]) {
                referenceOptions[name].forEach(option => input.add(new Option(option.label, option.value)));
            }
            if (Object.hasOwn(values, name)) {
                if (input.tagName === 'SELECT' && values[name] && ![...input.options].some(option => option.value === String(values[name]))) {
                    input.add(new Option(`${values[name]} (nilai draf)`, values[name]));
                }
                input.value = values[name];
            }
        });
        line.querySelector('[data-remove-item]').addEventListener('click', () => {
            if (container.children.length > 1) {
                line.remove();
                updateNames();
                saveDraft(false);
            }
        });
        container.appendChild(fragment);
        updateNames();
    }

    function collectData() {
        const metadata = Object.fromEntries(new FormData(form).entries());
        const materials = [...container.querySelectorAll('.request-item')].map(line => {
            const item = {};
            line.querySelectorAll('[data-field]').forEach(input => { item[input.dataset.field] = input.value; });
            return item;
        });
        return {
            metadata: {
                letter_number: metadata.letter_number || '',
                requesting_division: metadata.requesting_division || '',
                requesting_department: metadata.requesting_department || '',
                request_date: metadata.request_date || '',
            },
            materials,
        };
    }

    function saveDraft(showMessage = true) {
        try {
            localStorage.setItem(draftKey, JSON.stringify(collectData()));
            if (showMessage) status.textContent = 'Draf tersimpan di browser ini.';
        } catch (error) {
            status.textContent = 'Draf tidak dapat disimpan pada browser ini.';
        }
    }

    function restoreDraft() {
        const rawDraft = localStorage.getItem(draftKey);
        if (!rawDraft) return false;
        const draft = JSON.parse(rawDraft);
        Object.entries(draft.metadata || {}).forEach(([name, value]) => {
            const input = form.elements.namedItem(name);
            if (input) input.value = value;
        });
        container.replaceChildren();
        (draft.materials || []).slice(0, 10).forEach(material => addMaterialLine(material));
        return container.children.length > 0;
    }

    function setCell(sheet, address, value, numeric = false) {
        if (value === '' || value === null || value === undefined) {
            delete sheet[address];
            return;
        }
        sheet[address] = { ...(sheet[address] || {}), v: numeric ? Number(value) : String(value), t: numeric ? 'n' : 's' };
    }

    function downloadFilledWorkbook() {
        if (!form.reportValidity()) return;
        const button = document.getElementById('download-request');
        button.disabled = true;
        status.textContent = 'Menyiapkan Form LPPM...';

        fetch(templateUrl)
            .then(response => {
                if (!response.ok) throw new Error('Template Form LPPM tidak dapat dibuka.');
                return response.arrayBuffer();
            })
            .then(buffer => {
                const workbook = XLSX.read(new Uint8Array(buffer), { type: 'array', cellStyles: true });
                const sheet = workbook.Sheets.FORM;
                if (!sheet) throw new Error('Sheet FORM tidak ditemukan pada template.');
                const data = collectData();
                const metadata = data.metadata;
                setCell(sheet, 'C3', metadata.letter_number);
                setCell(sheet, 'C4', metadata.requesting_division);
                setCell(sheet, 'C5', metadata.requesting_department);
                if (metadata.request_date) {
                    const date = new Date(`${metadata.request_date}T00:00:00`);
                    setCell(sheet, 'C6', Math.floor(date.getTime() / 86400000) + 25569, true);
                    sheet.C6.z = 'dd/mm/yyyy';
                }
                for (let row = 12; row <= 21; row++) {
                    for (let column = 0; column < 18; column++) {
                        const address = XLSX.utils.encode_cell({ r: row - 1, c: column });
                        if (sheet[address]) delete sheet[address].v;
                    }
                }
                data.materials.forEach((material, index) => {
                    columnFields.forEach((field, column) => {
                        const address = XLSX.utils.encode_cell({ r: index + 11, c: column });
                        setCell(sheet, address, material[field], ['peinh', 'verpr'].includes(field));
                    });
                });
                XLSX.writeFile(workbook, `Form_LPPM_${(metadata.letter_number || 'Pengajuan').replace(/[^a-zA-Z0-9_-]/g, '_')}.xlsx`);
                saveDraft(false);
                status.textContent = 'Form Excel berhasil diunduh.';
            })
            .catch(error => { status.textContent = error.message; })
            .finally(() => { button.disabled = false; });
    }

    document.getElementById('add-material-row').addEventListener('click', () => {
        if (container.children.length < 10) {
            addMaterialLine();
            saveDraft(false);
        }
    });
    document.getElementById('save-request-draft').addEventListener('click', () => saveDraft());
    document.getElementById('download-request').addEventListener('click', downloadFilledWorkbook);
    form.addEventListener('input', () => saveDraft(false));

    loadReferenceOptions()
        .then(() => {
            if (document.getElementById('request-page').dataset.requestSent === '1') {
                localStorage.removeItem(draftKey);
                addMaterialLine();
            } else if (!restoreDraft()) {
                addMaterialLine();
            }
            status.textContent = `${optionNames.reduce((total, field) => total + referenceOptions[field].length, 0)} pilihan Form LPPM dimuat.`;
        })
        .catch(error => {
            addMaterialLine();
            status.textContent = error.message;
        });
})();
