import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import type { VideoLink } from './video-list';
import { VideoList } from './video-list';

const video: VideoLink = {
    id: 1,
    provider: 'YOUTUBE',
    video_id: 'aircAruvnKk',
    title: 'But what is a neural network?',
    instructor: '3Blue1Brown',
    description: 'La intuición antes de las fórmulas.',
    duration: '18:40',
    language: 'en',
    thumbnail_url: 'https://i.ytimg.com/vi/aircAruvnKk/hqdefault.jpg',
    note: null,
    start_seconds: 60,
};

describe('VideoList', () => {
    it('shows the thumbnail and loads the player only when asked', async () => {
        const { container } = render(<VideoList videos={[video]} />);

        expect(container.querySelector('iframe')).toBeNull();
        expect(container).toHaveTextContent('3Blue1Brown');
        expect(container).toHaveTextContent('18:40');
        expect(container).toHaveTextContent(
            'La intuición antes de las fórmulas.',
        );

        await userEvent.click(
            screen.getByRole('button', {
                name: 'Reproducir: But what is a neural network?',
            }),
        );

        const player = screen.getByTitle('But what is a neural network?');
        expect(player).toHaveAttribute(
            'src',
            'https://www.youtube-nocookie.com/embed/aircAruvnKk?autoplay=1&start=60',
        );
    });

    it('prefers the note of the lesson over the description of the video', () => {
        const { container } = render(
            <VideoList
                videos={[{ ...video, note: 'Mira del minuto 1 al 5.' }]}
            />,
        );

        expect(container).toHaveTextContent('Mira del minuto 1 al 5.');
        expect(container).not.toHaveTextContent('La intuición');
    });

    it('never builds a player from an ID that is not one', () => {
        const { container } = render(
            <VideoList videos={[{ ...video, video_id: 'x"><script>' }]} />,
        );

        expect(container.querySelector('button, iframe')).toBeNull();
    });
});
