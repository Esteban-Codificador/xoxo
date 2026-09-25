import type { SVGAttributes } from 'react';

/** Product mark: three connected nodes, the knowledge graph in miniature. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 40 40"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <path
                d="M10 30 20 10M20 10 30 30M10 30h20"
                fill="none"
                stroke="currentColor"
                strokeWidth="3"
                strokeLinecap="round"
            />
            <circle cx="20" cy="10" r="6" />
            <circle cx="10" cy="30" r="6" />
            <circle cx="30" cy="30" r="6" />
        </svg>
    );
}
