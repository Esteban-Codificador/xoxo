import { t } from '@/i18n';
import type { AuditChange } from './activity';

/**
 * The fields an audit entry changed, as before → after. Bodies and
 * descriptions only show their digest: the version history holds the text.
 */
export function AuditChanges({ changes }: { changes: AuditChange[] }) {
    return (
        <table className="w-full table-fixed text-xs">
            <thead className="text-left text-muted-foreground">
                <tr>
                    <th scope="col" className="w-1/4 py-1 pr-3 font-medium">
                        {t('admin.audit.field')}
                    </th>
                    <th scope="col" className="py-1 pr-3 font-medium">
                        {t('admin.audit.before')}
                    </th>
                    <th scope="col" className="py-1 font-medium">
                        {t('admin.audit.after')}
                    </th>
                </tr>
            </thead>
            <tbody>
                {changes.map((change) => (
                    <tr key={change.field} className="border-t align-top">
                        <th
                            scope="row"
                            className="py-1.5 pr-3 text-left font-mono font-normal [overflow-wrap:anywhere]"
                        >
                            {change.field}
                        </th>
                        <td className="py-1.5 pr-3 [overflow-wrap:anywhere]">
                            <Value
                                value={change.before}
                                hashed={change.hashed}
                            />
                        </td>
                        <td className="py-1.5 [overflow-wrap:anywhere]">
                            <Value
                                value={change.after}
                                hashed={change.hashed}
                            />
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

function Value({ value, hashed }: { value: string | null; hashed: boolean }) {
    if (value === null) {
        return (
            <span className="text-muted-foreground italic">
                {t('admin.audit.none')}
            </span>
        );
    }

    if (hashed) {
        return (
            <span className="text-muted-foreground">
                {t('admin.audit.hashed', { hash: value })}
            </span>
        );
    }

    return <span className="whitespace-pre-wrap">{value}</span>;
}
