/// <reference types="jquery" />
export declare function setJQuery(jq: any): void;
export interface IDefaults {
    i18n: {
        button: string;
    };
    left: number;
    leftColumns?: number;
    right: number;
    rightColumns?: number;
}
export interface IS {
    barWidth: number;
    dt: any;
    rtl: boolean;
}
export interface IClasses {
    fixedLeft: string;
    fixedRight: string;
    leftBottomBlocker: string;
    leftTopBlocker: string;
    rightBottomBlocker: string;
    rightTopBlocker: string;
    tableFixedLeft: string;
    tableFixedRight: string;
}
export interface IDOM {
    leftBottomBlocker: JQuery<HTMLElement>;
    leftTopBlocker: JQuery<HTMLElement>;
    rightBottomBlocker: JQuery<HTMLElement>;
    rightTopBlocker: JQuery<HTMLElement>;
}
export interface ICellCSS {
    left?: string;
    position: string;
    right?: string;
}
export default class FixedColumns {
    private static version;
    private static classes;
    private static defaults;
    classes: IClasses;
    c: IDefaults;
    dom: IDOM;
    s: IS;
    constructor(settings: any, opts: IDefaults);
    /**
     * Getter/Setter for the fixedColumns.left property
     *
     * @param newVal Optional. If present this will be the new value for the number of left fixed columns
     * @returns The number of left fixed columns
     */
    left(newVal?: number): number;
    /**
     * Getter/Setter for the fixedColumns.left property
     *
     * @param newVal Optional. If present this will be the new value for the number of right fixed columns
     * @returns The number of right fixed columns
     */
    right(newVal?: number): number;
    /**
     * Iterates over the columns, fixing the appropriate ones to the left and right
     */
    private _addStyles;
    /**
     * Gets the correct CSS for the cell, header or footer based on options provided
     *
     * @param header Whether this cell is a header or a footer
     * @param dist The distance that the cell should be moved away from the edge
     * @param lr Indicator of fixing to the left or the right
     * @returns An object containing the correct css
     */
    private _getCellCSS;
    /**
     * Gets the css that is required to clear the fixing to a side
     *
     * @param lr Indicator of fixing to the left or the right
     * @returns An object containing the correct css
     */
    private _clearCellCSS;
    private _setKeyTableListener;
}
