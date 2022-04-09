import * as typeInterfaces from './paneType';
export declare function setJQuery(jq: any): void;
export default class SearchPane {
    private static version;
    private static classes;
    private static defaults;
    classes: typeInterfaces.IClasses;
    dom: typeInterfaces.IDOM;
    c: typeInterfaces.IDefaults;
    s: typeInterfaces.IS;
    colExists: boolean;
    selections: any;
    customPaneSettings: typeInterfaces.IConfigPaneItem;
    /**
     * Creates the panes, sets up the search function
     *
     * @param paneSettings The settings for the searchPanes
     * @param opts The options for the default features
     * @param idx the index of the column for this pane
     * @returns {object} the pane that has been created, including the table and the index of the pane
     */
    constructor(paneSettings: any, opts: any, idx: any, layout: any, panesContainer: any, panes?: any);
    /**
     * Adds a row to the panes table
     *
     * @param display the value to be displayed to the user
     * @param filter the value to be filtered on when searchpanes is implemented
     * @param shown the number of rows in the table that are currently visible matching this criteria
     * @param total the total number of rows in the table that match this criteria
     * @param sort the value to be sorted in the pane table
     * @param type the value of which the type is to be derived from
     */
    addRow(display: any, filter: any, shown: number, total: number | string, sort: any, type: any, className?: string): any;
    /**
     * Adjusts the layout of the top row when the screen is resized
     */
    adjustTopRow(): void;
    /**
     * In the case of a rebuild there is potential for new data to have been included or removed
     * so all of the rowData must be reset as a precaution.
     */
    clearData(): void;
    /**
     * Clear the selections in the pane
     */
    clearPane(): this;
    /**
     * Collapses the pane so that only the header is displayed
     */
    collapse(): void;
    /**
     * Strips all of the SearchPanes elements from the document and turns all of the listeners for the buttons off
     */
    destroy(): void;
    /**
     * Getting the legacy message is a little complex due a legacy parameter
     */
    emptyMessage(): string;
    /**
     * Updates the number of filters that have been applied in the title
     */
    getPaneCount(): number;
    /**
     * Rebuilds the panes from the start having deleted the old ones
     *
     * @param? last boolean to indicate if this is the last pane a selection was made in
     * @param? dataIn data to be used in buildPane
     * @param? init Whether this is the initial draw or not
     * @param? maintainSelection Whether the current selections are to be maintained over rebuild
     */
    rebuildPane(last?: boolean, dataIn?: any, init?: any, maintainSelection?: boolean): this;
    /**
     * removes the pane from the page and sets the displayed property to false.
     */
    removePane(): void;
    /**
     * Resizes the pane based on the layout that is passed in
     *
     * @param layout the layout to be applied to this pane
     */
    resize(layout: string): void;
    /**
     * Sets the cascadeRegen property of the pane. Accessible from above because as SearchPanes.ts
     * deals with the rebuilds.
     *
     * @param val the boolean value that the cascadeRegen property is to be set to
     */
    setCascadeRegen(val: boolean): void;
    /**
     * This function allows the clearing property to be assigned. This is used when implementing cascadePane.
     * In setting this to true for the clearing of the panes selection on the deselects it forces the pane to
     * repopulate from the entire dataset not just the displayed values.
     *
     * @param val the boolean value which the clearing property is to be assigned
     */
    setClear(val: boolean): void;
    /**
     * Expands the pane from the collapsed state
     */
    show(): void;
    /**
     * Updates the values of all of the panes
     *
     * @param draw whether this has been triggered by a draw event or not
     */
    updatePane(draw?: boolean): void;
    /**
     * Updates the panes if one of the options to do so has been set to true
     * rather than the filtered message when using viewTotal.
     */
    updateTable(): void;
    /**
     * Sets the listeners for the pane.
     *
     * Having it in it's own function makes it easier to only set them once
     */
    _setListeners(): boolean;
    /**
     * Takes in potentially undetected rows and adds them to the array if they are not yet featured
     *
     * @param filter the filter value of the potential row
     * @param display the display value of the potential row
     * @param sort the sort value of the potential row
     * @param type the type value of the potential row
     * @param arrayFilter the array to be populated
     * @param bins the bins to be populated
     */
    private _addOption;
    /**
     * Method to construct the actual pane.
     *
     * @param selectedRows previously selected Rows to be reselected
     * @last boolean to indicate whether this pane was the last one to have a selection made
     */
    private _buildPane;
    /**
     * Update the array which holds the display and filter values for the table
     */
    private _detailsPane;
    /**
     * Appends all of the HTML elements to their relevant parent Elements
     */
    private _displayPane;
    /**
     * Escape html characters within a string
     *
     * @param txt the string to be escaped
     * @returns the escaped string
     */
    private _escapeHTML;
    /**
     * Gets the options for the row for the customPanes
     *
     * @returns {object} The options for the row extended to include the options from the user.
     */
    private _getBonusOptions;
    /**
     * Adds the custom options to the pane
     *
     * @returns {Array} Returns the array of rows which have been added to the pane
     */
    private _getComparisonRows;
    /**
     * Gets the options for the row for the customPanes
     *
     * @returns {object} The options for the row extended to include the options from the user.
     */
    private _getOptions;
    /**
     * This method allows for changes to the panes and table to be made when a selection or a deselection occurs
     *
     * @param select Denotes whether a selection has been made or not
     */
    private _makeSelection;
    /**
     * Fill the array with the values that are currently being displayed in the table
     *
     * @param last boolean to indicate whether this was the last pane a selection was made in
     */
    private _populatePane;
    /**
     * Populates an array with all of the data for the table
     *
     * @param rowIdx The current row index to be compared
     * @param arrayFilter The array that is to be populated with row Details
     * @param bins The bins object that is to be populated with the row counts
     */
    private _populatePaneArray;
    /**
     * Reloads all of the previous selects into the panes
     *
     * @param loadedFilter The loaded filters from a previous state
     */
    private _reloadSelect;
    /**
     * This method decides whether a row should contribute to the pane or not
     *
     * @param filter the value that the row is to be filtered on
     * @param dataIndex the row index
     */
    private _search;
    /**
     * Creates the contents of the searchCont div
     *
     * NOTE This is overridden when semantic ui styling in order to integrate the search button into the text box.
     */
    private _searchContSetup;
    /**
     * Adds outline to the pane when a selection has been made
     */
    private _searchExtras;
    /**
     * Finds the ratio of the number of different options in the table to the number of rows
     *
     * @param bins the number of different options in the table
     * @param rowCount the total number of rows in the table
     * @returns {number} returns the ratio
     */
    private _uniqueRatio;
    /**
     * updates the options within the pane
     *
     * @param draw a flag to define whether this has been called due to a draw event or not
     */
    private _updateCommon;
}
