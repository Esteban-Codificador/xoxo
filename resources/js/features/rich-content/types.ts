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

/** Where to load an image of the content from, and its size (MediaSources). */
export type MediaSource = {
    url: string;
    width: number;
    height: number;
};

/**
 * The images a page's content shows, by media id. RichContent only stores
 * ids: pages send this map next to it (ADR-034).
 */
export type MediaMap = Partial<Record<number, MediaSource>>;
