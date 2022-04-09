/// <reference types="jquery" />
/// <reference types="datatables.net" />
import * as typeInterfaces from './panesType';
export declare function setJQuery(jq: any): void;
import SearchPane from './searchPane';
export default class SearchPanes {
    private static version;
    private static classes;
    private static defaults;
    classes: typeInterfaces.IClasses;
    dom: typeInterfaces.IDOM;
    c: typeInterfaces.IDefaults;
    s: typeInterfaces.IS;
    regenerating: boolean;
    constructor(paneSettings: any, opts: any, fromInit?: boolean);
    /**
     * Clear the selections of all of the panes
     */
    clearSelections(): SearchPane[];
    /**
     * returns the container node for the searchPanes
     */
    getNode(): JQuery<HTMLElement>;
    /**
     * rebuilds all of the panes
     */
    rebuild(targetIdx?: boolean | number, maintainSelection?: boolean): SearchPane | SearchPane[];
    /**
     * Redraws all of the panes
     */
    redrawPanes(rebuild?: boolean): void;
    /**
     * Resizes all of the panes
     */
    resizePanes(): SearchPanes;
    /**
     * Attach the panes, buttons and title to the document
     */
    private _attach;
    /**
     * Attach the top row containing the filter count and clear all button
     */
    private _attachExtras;
    /**
     * If there are no panes to display then this method is called to either
     * display a message in their place or hide them completely.
     */
    private _attachMessage;
    /**
     * Attaches the panes to the document and displays a message or hides if there are none
     */
    private _attachPaneContainer;
    /**
     * Prepares the panes for selections to be made when cascade is active and a deselect has occured
     *
     * @param newSelectionList the list of selections which are to be made
     */
    private _cascadeRegen;
    /**
     * Attaches the message to the document but does not add any panes
     */
    private _checkMessage;
    /**
     * Checks which panes are collapsed and then performs relevant actions to the collapse/show all buttons
     *
     * @param pane The pane to be checked
     */
    private _checkCollapse;
    /**
     * Collapses all of the panes
     */
    private _collapseAll;
    /**
     * Escape html characters within a string
     *
     * @param txt the string to be escaped
     * @returns the escaped string
     */
    private _escapeHTML;
    /**
     * Gets the selection list from the previous state and stores it in the selectionList Property
     */
    private _getState;
    /**
     * Makes all of the selections when cascade is active
     *
     * @param newSelectionList the list of selections to be made, in the order they were originally selected
     */
    private _makeCascadeSelections;
    /**
     * Declares the instances of individual searchpanes dependant on the number of columns.
     * It is necessary to run this once preInit has completed otherwise no panes will be
     * created as the column count will be 0.
     *
     * @param table the DataTable api for the parent table
     * @param paneSettings the settings passed into the constructor
     * @param opts the options passed into the constructor
     */
    private _paneDeclare;
    /**
     * Finds a pane based upon the name of that pane
     *
     * @param name string representing the name of the pane
     * @returns SearchPane The pane which has that name
     */
    private _findPane;
    /**
     * Works out which panes to update when data is recieved from the server and viewTotal is active
     */
    private _serverTotals;
    /**
     * Sets the listeners for the collapse and show all buttons
     * Also sets and performs checks on current panes to see if they are collapsed
     */
    private _setCollapseListener;
    /**
     * Shows all of the panes
     */
    private _showAll;
    /**
     * Initialises the tables previous/preset selections and initialises callbacks for events
     *
     * @param table the parent table for which the searchPanes are being created
     */
    private _startup;
    private _prepViewTotal;
    /**
     * Updates the number of filters that have been applied in the title
     */
    private _updateFilterCount;
    /**
     * Updates the selectionList when cascade is not in place
     */
    private _updateSelection;
}
