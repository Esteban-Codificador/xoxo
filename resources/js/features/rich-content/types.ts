/**
 * RichContent as sent by the server (ADR-023): a versioned envelope around
 * a ProseMirror document that was validated against RichContentSchema.
 */
export type RichMark = {
    type: string;
    attrs?: Record<string, unknown>;
};

export type RichNode = {
    type: string;
    attrs?: Record<string, unknown>;
    content?: RichNode[];
    text?: string;
    marks?: RichMark[];
};

export type RichContent = {
    version: 1;
    doc: RichNode;
};

export type Heading = {
    id: string;
    text: string;
    level: number;
};
