'use strict';

/**
 * Smart table module
 *
 * @author <sanych.zp@gmail.com> Alex Krupko
 * @author <krasnyansky.v@gmail.com> Slava Krasnyansky
 * @date 20 Jan 2017
 */
!function()
{
    let dataTableModule = angular.module('SmartTable', []);

    function SmartTable(containerElement, scope, tableKey)
    {
        /**
         * Get angular services
         */
        let $timeout = angular.element(document.body).injector().get('$timeout');
        let $translate = angular.element(document.body).injector().get('$translate');
        let $state = angular.element(document.body).injector().get('$state');

        /**
         * Local storage key for storing table options
         */
        let storageKey = `SmartTableOptions#${$state.current.name}#${tableKey}`;

        /**
         * Array with original data
         */
        let originalData = [];

        /**
         * Array with filtered data
         */
        let filteredData = [];

        /**
         * Array with sorted data
         */
        let sortedData = [];

        /**
         * Array with sliced data
         * This data is being put in scope $smartTableData variable
         */
        let slicedData = [];

        /**
         * Showing info block options
         */
        let showingInfo = null;

        /**
         * Local storage data object
         */
        let storageData = null;

        /**
         * Sort options
         */
        this.sorting = {
            column   : null,
            isAsc    : true,
            fields   : {},
            available: false,
            enabled  : false
        };

        /**
         * Filter options
         */
        this.filtering = {
            fields     : [],
            filter     : '',
            placeholder: '',
            available  : false,
            enabled    : false
        };

        /**
         * Pagination options
         */
        this.paging = {
            page            : 1,
            total           : 1,
            perPage         : 10,
            availablePerPage: [10, 25, 50, 100, 200],
            available       : false,
            enabled         : false,
            perPageEnabled  : false,
            perPageAvailable: false
        };

        /**
         * Unique id of instance
         */
        this.id = new Date().getUTCMilliseconds();


        /**
         * Updates scope variable
         */
        let updateData = () =>
        {
            $timeout(() =>
            {
                scope.$smartTableData = slicedData;
                updateHighlighting();
                updateShowingInfo();
            });
        };

        /**
         * Updates showing info block
         */
        let updateShowingInfo = () =>
        {
            if (showingInfo) {
                let options = {
                    start     : (this.paging.page - 1) * this.paging.perPage + 1,
                    end       : this.paging.page * this.paging.perPage,
                    filtered  : filteredData.length,
                    total     : originalData.length,
                    isFiltered: !!this.filtering.filter,
                    isPages   : this.paging.total > 1,
                };
                options.end > options.filtered && (options.end = options.filtered);

                $translate(showingInfo.translateKey, options).then(translate => showingInfo.element.innerHTML = translate);
            }
        };

        /**
         * Calculates amount of pages
         */
        let calculatePages = () =>
        {
            this.paging.total = Math.ceil(filteredData.length / this.paging.perPage);
            this.paging.page > this.paging.total && (this.paging.page = this.paging.total);
            this.paging.page < 1 && (this.paging.page = 1);
        };

        /**
         * Filters original data and calls sort function
         */
        let filter = () =>
        {
            if (!this.filtering.fields.length || !this.filtering.filter) {
                filteredData = originalData.slice();
            } else {
                let regexp = new RegExp(this.filtering.filter, 'i');
                filteredData = originalData.filter(item =>
                {
                    for (let i = this.filtering.fields.length; i--; ) {
                        try {
                            if (regexp.test(item[this.filtering.fields[i]])) {
                                return true;
                            }
                        } catch (e) {}
                    }

                    return false;
                });
            }

            containerElement.classList.toggle('smart-table-once-item', filteredData.length < 2);

            calculatePages();
            setSortingAvailability();
            setPagingAvailability();
            sort();
        };

        /**
         * Sorts filtered data and calls slice function
         */
        let sort = () =>
        {
            if (!this.sorting.enabled || !this.sorting.column) {
                sortedData = filteredData.slice();
            } else {
                sortedData = filteredData.sort((a, b) =>
                {
                    a = (a[this.sorting.column] === undefined || a[this.sorting.column] === null) ? '' : a[this.sorting.column];
                    b = (b[this.sorting.column] === undefined || b[this.sorting.column] === null) ? '' : b[this.sorting.column];

                    typeof a === 'string' && (a = a.toLowerCase());
                    typeof b === 'string' && (b = b.toLowerCase());

                    return (a < b ? -1 : (a > b ? 1 : 0)) * (this.sorting.isAsc ? 1 : -1);
                });
            }

            slice();
        };

        /**
         * Slices sorted data for current page and calls updateData function
         */
        let slice = () =>
        {
            slicedData = this.paging.enabled
                ? sortedData.slice((this.paging.page - 1) * this.paging.perPage, this.paging.page * this.paging.perPage)
                : sortedData.slice();

            updateData();
        };

        /**
         * Updates highlighting
         */
        let updateHighlighting = () =>
        {
            let headSorted = containerElement.querySelectorAll(`.thead [table-sort]`);
            headSorted.classList.remove('sorted-asc', 'sorted-desc');
            headSorted.filter(`:not([table-sort="${this.sorting.column}"])`).classList.remove('sorted');
            headSorted.filter(`[table-sort="${this.sorting.column}"]`).classList.add('sorted', this.sorting.isAsc ? 'sorted-asc' : 'sorted-desc');

            let bodySorted = containerElement.querySelectorAll(`.tbody [table-sort]`);
            bodySorted.filter(`:not([table-sort="${this.sorting.column}"])`).classList.remove('sorted');
            bodySorted.filter(`[table-sort="${this.sorting.column}"]`).classList.add('sorted');
        };

        /**
         * Finds the first visible table and scrolls page to it if table top is out of screen now
         */
        let scrollToTable = () =>
        {
            let tables = containerElement.querySelectorAll('.smart-table');
            for (let i = 0; i < tables.length; ++i) {
                if (tables[i].offsetParent) {
                    let top = tables[i].getBoundingClientRect().top;
                    if (top < 0) {
                        return window.scrollTo(0, window.scrollY + top);
                    }
                }
            }
        };

        /**
         * Reads and returns options from local storage
         *
         * @return {Object}
         */
        let getStorageOptions = () =>
        {
            if (!storageData) {
                try {
                    storageData = window.JSON.parse(window.localStorage.getItem(storageKey)) || {};
                } catch (e) {
                    storageData = {};
                }
            }

            return storageData;
        };

        /**
         * Saves options to local storage
         */
        let saveStorageOptions = () =>
        {
            storageData = {
                perPage      : this.paging.perPage,
                sortingColumn: this.sorting.column,
                sortingIsAsc : this.sorting.isAsc
            };

            window.localStorage.setItem(storageKey, window.JSON.stringify(storageData));
        };

        /**
         * Sets sorting available flag
         */
        let setSortingAvailability = () =>
        {
            this.sorting.available = this.sorting.enabled && filteredData.length > 1;
        };

        /**
         * Sets paging available flag
         */
        let setPagingAvailability = () =>
        {
            this.paging.available = this.paging.enabled && this.paging.total > 1;
            this.paging.perPageAvailable = this.paging.perPageEnabled && filteredData.length >= Math.min.apply(null, this.paging.availablePerPage);
        };

        /**
         * Sets filter available flag
         */
        let setFilteringAvailability = () =>
        {
            this.filtering.available = this.filtering.enabled && originalData.length > 1;
        };

        /**
         * Init smart table
         */
        (() =>
        {
            let storageOptions = getStorageOptions();
            this.paging.availablePerPage.indexOf(storageOptions.perPage) >= 0 && (this.paging.perPage = storageOptions.perPage);
        })();


        /**
         * Sets data for table
         *
         * @param {Array} data Array of data objects
         */
        this.setData = data =>
        {
            if (data !== undefined) {
                if (!Array.isArray(data)) {
                    throw new Error('SmartTable: Value of smart-table-init directive must be an array');
                }

                originalData = data;
                setFilteringAvailability();
                filter();
            }
        };

        /**
         * Adds sortable cell
         *
         * @param {String}  column
         * @param {Element} element
         */
        this.addSortableCell = (column, element) =>
        {
            this.sorting.column === column && element.classList.add('sorted');
        };

        /**
         * Adds sortable column
         *
         * @param {String}  column
         * @param {Element} element
         */
        this.addSortableColumn = (column, element) =>
        {
            this.sorting.enabled = true;
            setSortingAvailability();

            element.classList.add('sortable');

            element.addEvent('click', () =>
            {
                if (filteredData.length < 2) {
                    return;
                }

                if (this.sorting.column === column) {
                    this.sorting.isAsc = !this.sorting.isAsc;
                } else {
                    this.sorting.column = column;
                    this.sorting.isAsc = true;
                }

                sort();
                saveStorageOptions();
            });

            if (!this.sorting.column) {
                this.sorting.column = column;

                let storageOptions = getStorageOptions();
                if (storageOptions.sortingColumn === column) {
                    this.sorting.column = storageOptions.sortingColumn;
                    this.sorting.isAsc = storageOptions.sortingIsAsc;
                }

                sort();
            }
        };

        /**
         * Sets default data sorting
         *
         * @param {String} column
         * @param {String} direction
         */
        this.setDefaultSorting = (column, direction) =>
        {
            if (this.sorting.column !== getStorageOptions().sortingColumn) {
                this.sorting.column = column;
                this.sorting.isAsc = direction.toLowerCase() === 'asc';

                sort();
            }
        };

        /**
         * Sets element and translation key for showing info block
         *
         * @param {Element} element
         * @param {String}  translateKey
         */
        this.setShowingInfoBlock = (element, translateKey) =>
        {
            showingInfo = {element, translateKey};
            element.classList.add('smart-table-showing-info');
            updateShowingInfo();
            scope.$on('languageChangeSuccess', updateShowingInfo);
        };

        /**
         * Sets page
         *
         * @param {Number} page
         */
        this.setPage = page =>
        {
            if (page > 0 && page <= this.paging.total) {
                this.paging.page = page;

                slice();
                scrollToTable();
            }
        };

        /**
         * Sets items amount per page
         *
         * @param {Number} amount
         */
        this.setPerPageAmount = amount =>
        {
            if (this.paging.availablePerPage.indexOf(amount) >= 0) {
                this.paging.perPage = amount;

                calculatePages();
                setPagingAvailability();
                slice();
                scrollToTable();
                saveStorageOptions();
            }
        };

        /**
         * Enables pagination
         */
        this.enableViewOptions = () =>
        {
            this.paging.enabled = true;
            this.paging.perPageEnabled = true;
            setPagingAvailability();
            slice();
        };

        /**
         * Filters data
         */
        this.setFilterValue = value =>
        {
            this.filtering.filter = value;
            filter();
        };

        /**
         * Sets fields for filtering
         *
         * @param {Array} fields
         */
        this.setFilterFields = fields =>
        {
            this.filtering.fields = fields;
            this.filtering.enabled = true;
            setFilteringAvailability();
            filter();
        };

        /**
         * Prepares options for mobile options panel
         */
        this.setMobileOptions = () => $timeout(() =>
        {
            scope.$smartTable.mobileOptions = {
                filter : this.filtering.filter,
                sorting: {
                    fields: this.sorting.fields,
                    column: this.sorting.column,
                    isAsc : this.sorting.isAsc
                },
                paging : {
                    availablePerPage: this.paging.availablePerPage,
                    perPage         : this.paging.perPage
                }
            };
        });

        /**
         * Applies options from mobile options panel
         */
        this.applyMobileOptions = () =>
        {
            this.filtering.filter = scope.$smartTable.mobileOptions.filter;
            this.sorting.column = scope.$smartTable.mobileOptions.sorting.column;
            this.sorting.isAsc = scope.$smartTable.mobileOptions.sorting.isAsc;
            this.paging.perPage = scope.$smartTable.mobileOptions.paging.perPage;

            filter();
            scope.mobileMenus.closeTableOptions();
        };
    }

    /**
     * smart-table-init directive
     * Initializes table block. Must take array with data
     */
    dataTableModule.directive('smartTableInit', () => ({
        restrict: 'A',
        scope: true,
        controller: [
            '$scope', '$attrs', '$element',
            ($scope, $attrs, $element) =>
            {
                if (!$attrs.smartTableInit) {
                    throw new Error('SmartTable: Value of smart-table-init directive is not specified');
                }

                $scope.$smartTable = new SmartTable($element[0], $scope, $attrs.smartTableInit);
                $scope.$watch($attrs.smartTableInit, newValue => $scope.$smartTable.setData(newValue), true);
            }
        ]
    }));

    /**
     * smart-table directive
     * Realizes sticking table head on scroll
     */
    dataTableModule.directive('smartTable', () => ({
        restrict: 'C',
        link: (scope, element) =>
        {
            if (element[0].classList.contains('stick-head')) {
                let thead = element[0].querySelector('.thead'),
                    tbody = element[0].querySelector('.thead + .tbody');

                if (thead && tbody) {
                    window.addEvent('resize', () =>
                    {
                        let bcr = element[0].getBoundingClientRect();
                        bcr.top <= 0 && element[0].classList.contains('sticked') && (thead.style.width = bcr.width + 'px');
                    });

                    window.addEvent('scroll', () =>
                    {
                        let tableBcr = element[0].getBoundingClientRect();
                        if (element[0].offsetParent && tableBcr.top <= 0) {
                            let headBcr = thead.getBoundingClientRect();
                            if (!element[0].classList.contains('sticked')) {
                                element[0].classList.add('sticked');
                                thead.style.width = tableBcr.width + 'px';
                                tbody.style.paddingTop = headBcr.height + 'px';
                            }

                            thead.classList.toggle('hidden', tableBcr.bottom < headBcr.height * 5);
                        } else if (tableBcr.top > 0 && element[0].classList.contains('sticked')) {
                            element[0].classList.remove('sticked');
                            thead.style.width = 'auto';
                            tbody.style.removeProperty('padding-top');
                        }
                    });
                }
            }
        }
    }));

    /**
     * table-sort directive
     * Sets sortable head if element is child of .thead
     * Sets cell for highlighting
     */
    dataTableModule.directive('tableSort', () => ({
        restrict: 'A',
        require: '^^smartTableInit',
        controller: [
            '$scope', '$element', '$attrs',
            ($scope, $element, $attrs) =>
            {
                if (!$attrs.tableSort) {
                   throw new Error('SmartTable: Value of table-sort directive is not specified');
                }

                if ($element[0].parents('.thead').length) {
                    $scope.$smartTable.addSortableColumn($attrs.tableSort, $element[0]);
                    $scope.$smartTable.sorting.fields[$attrs.tableSort] = ($element[0].children[0] || $element[0]).textContent;
                } else {
                    $scope.$smartTable.addSortableCell($attrs.tableSort, $element[0]);
                }
            }
        ]
    }));

    /**
     * table-sort-default directive
     * Sets default sorted column
     */
    dataTableModule.directive('tableSortDefault', () => ({
        restrict: 'A',
        require: 'tableSort',
        controller: [
            '$scope', '$element', '$attrs',
            ($scope, $element, $attrs) =>
            {
                if (!$attrs.tableSortDefault || ['asc', 'desc'].indexOf($attrs.tableSortDefault) < 0) {
                    throw new Error('SmartTable: Value of table-sort-default directive is not specified or invalid');
                }

                $element[0].parents('.thead').length && $scope.$smartTable.setDefaultSorting($attrs.tableSort, $attrs.tableSortDefault);
            }
        ]
    }));

    /**
     * table-showing-info directive
     * Shows showing information
     */
    dataTableModule.directive('tableShowingInfo', () => ({
        restrict: 'A',
        require: '^^smartTableInit',
        controller: [
            '$scope', '$element', '$attrs',
            ($scope, $element, $attrs) =>
            {
                if (!$attrs.tableShowingInfo) {
                    throw new Error('SmartTable: Value of table-showing-info directive is not specified');
                }

                $scope.$smartTable.setShowingInfoBlock($element[0], $attrs.tableShowingInfo);
            }
        ]
    }));

    /**
     * table-view-options directive
     * Adds block with table options (page switcher and amount per page switcher)
     */
    dataTableModule.directive('tableViewOptions', () => ({
        restrict: 'A',
        require: '^^smartTableInit',
        templateUrl: 'smartTableViewOptions',
        controller: [
            '$element', '$scope',
            ($element, $scope) =>
            {
                $element[0].classList.add('smart-table-view-options');
                $scope.$smartTable.enableViewOptions();

                let mobileOptionsBtn = $element[0].querySelector('.mobile-options-open-btn');
                mobileOptionsBtn && window.addEvent('scroll resize', () =>
                {
                    let bcrBlock = $element[0].parent('[smart-table-init]').getBoundingClientRect();
                    mobileOptionsBtn.classList.toggle('fixed', bcrBlock.top <= 50);
                    mobileOptionsBtn.classList.toggle('hidden', bcrBlock.bottom <= 150);
                });

                $scope.$on('SmartTableMobileOptionsOpened', $scope.$smartTable.setMobileOptions);
            }
        ]
    }));

    /**
     * table-filter directive
     * Adds block with filter field
     */
    dataTableModule.directive('tableFilter', () => ({
        restrict: 'A',
        require: '^^smartTableInit',
        templateUrl: 'smartTableFilter',
        controller: [
            '$element', '$scope', '$translate', '$attrs', '$timeout',
            ($element, $scope, $translate, $attrs, $timeout) =>
            {
                if (!$attrs.tableFilter) {
                    throw new Error('SmartTable: Value of table-filter directive is not specified');
                }

                function setPlaceholder()
                {
                    $translate($attrs.tableFilter).then(translate => $scope.$smartTable.filtering.placeholder = translate);
                }

                setPlaceholder();
                $scope.$on('languageChangeSuccess', setPlaceholder);
                $element[0].classList.add('smart-table-filter');

                let filterTimeout,
                    filterString = '';
                $scope.$watch('$smartTableFilter', value =>
                {
                    if (value !== undefined && value !== filterString) {
                        filterString = value;
                        $timeout.cancel(filterTimeout);
                        filterTimeout = $timeout(() =>
                        {
                            $scope.$smartTable.setFilterValue(value);
                        }, 500);
                    }
                });
            }
        ]
    }));

    /**
     * table-filter directive
     * Adds block with filter field
     */
    dataTableModule.directive('tableFilterFields', () => ({
        restrict: 'A',
        require: 'tableFilter',
        controller: [
            '$scope', '$attrs',
            ($scope, $attrs) => $scope.$smartTable.setFilterFields($attrs.tableFilterFields.trim().split(/,\s*/))
        ]
    }));
}();
