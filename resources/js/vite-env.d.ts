/// <reference types="vite/client" />

declare module '*.svg' {
    const content: string;
    export default content;
}

interface Window {
    Pusher: typeof import('pusher-js');
}
