/// <reference types="jquery" />
/// <reference types="datatables.net" />
export interface IClasses {
    badgePill?: string;
    bordered: string;
    buttonGroup: string;
    buttonSub: string;
    caret: string;
    clear: string;
    clearAll: string;
    clearButton: string;
    collapseAll: string;
    collapseButton: string;
    container: string;
    countButton: string;
    disabledButton: string;
    hidden: string;
    hide: string;
    layout: string;
    name: string;
    nameButton: string;
    nameCont: string;
    narrow: string;
    narrowButton?: string;
    narrowSearch?: string;
    narrowSub?: string;
    paneButton: string;
    paneInputButton: string;
    pill: string;
    rotated: string;
    search: string;
    searchCont: string;
    searchIcon: string;
    searchLabelCont: string;
    selected: string;
    show?: string;
    smallGap: string;
    subRow1: string;
    subRow2: string;
    subRowsContainer: string;
    table?: string;
    title: string;
    topRow: string;
}
export interface IConfigPaneItem {
    className: string;
    dtOpts: {
        [keys: string]: any;
    };
    header: string;
    name: string;
    options: IOption[];
    preSelect: string[];
}
export interface IDataArray {
    display: any;
    filter: any;
    sort: any;
    type: string;
}
export interface IDataArrayST extends IDataArray {
    shown: number;
    total: number;
}
export interface IDefaults {
    clear: boolean;
    collapse: boolean;
    combiner: string;
    container: (dt: any) => HTMLElement;
    controls: boolean;
    dtOpts: {
        [keys: string]: any;
    };
    emptyMessage: string;
    hideCount: boolean;
    i18n: {
        clearPane: string;
        count: string;
        countFiltered: string;
        emptyMessage: string;
    };
    initCollapsed: boolean;
    layout: string;
    name: string;
    orderable: boolean;
    orthogonal: IOrthogonal;
    preSelect: any;
    threshold: number;
    viewCount: boolean;
}
export interface IDOM {
    buttonGroup: JQuery<HTMLElement>;
    clear: JQuery<HTMLElement>;
    collapseButton: JQuery<HTMLElement>;
    container: JQuery<HTMLElement>;
    countButton: JQuery<HTMLElement>;
    dtP: JQuery<HTMLElement>;
    lower: JQuery<HTMLElement>;
    nameButton: JQuery<HTMLElement>;
    panesContainer: JQuery<HTMLElement>;
    searchBox: JQuery<HTMLElement>;
    searchButton: JQuery<HTMLElement>;
    searchCont: JQuery<HTMLElement>;
    searchLabelCont: JQuery<HTMLElement>;
    topRow: JQuery<HTMLElement>;
    upper: JQuery<HTMLElement>;
}
export interface IIndexes {
    filter: any;
    index: number;
}
export interface IOption {
    label: string;
    values: (rowData: any, rowIdx: string) => boolean;
}
export interface IOrthogonal {
    display: string;
    filter: string;
    hideCount: boolean;
    search: string;
    show: boolean;
    sort: string;
    threshold: number;
    type: string;
    viewCount: boolean;
}
export interface IRowData {
    arrayFilter: IDataArray[];
    arrayOriginal: IDataArray[];
    bins: {
        [keys: string]: number;
    };
    binsOriginal: {
        [keys: string]: number;
    };
    filterMap: Map<number, any>;
    totalOptions: number;
}
export interface IRowDataST extends IRowData {
    arrayFilter: IDataArrayST[];
    arrayOriginal: IDataArrayST[];
    arrayShown: IDataArrayST[];
    bins: {
        [keys: string]: number;
    };
    binsOriginal: {
        [keys: string]: number;
    };
    binsShown: {
        [keys: string]: number;
    };
    filterMap: Map<number, any>;
    totalOptions: number;
}
export interface IS {
    colExists: boolean;
    colOpts: any;
    customPaneSettings: IConfigPaneItem;
    displayed: boolean;
    dt: any;
    dtPane: any;
    firstSet: boolean;
    index: number;
    indexes: IIndexes[];
    listSet: boolean;
    name: string;
    rowData: IRowData;
    scrollTop: number;
    searchFunction: any;
    selections: any[];
    serverSelect: any;
    serverSelecting: boolean;
    tableLength: number;
    updating: boolean;
}
export interface ISST extends IS {
    filteringActive: boolean;
    rowData: IRowDataST;
}
