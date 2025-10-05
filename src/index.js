import React from 'react';
import "./index.css";
import { createRoot } from 'react-dom/client';

// Simple React component
function App() {
    return (
        <div>
            <h1>Hello, Post Series!</h1>
            <p>React is now working in your WordPress plugin.</p>
        </div>
    );
}

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function () {
    // Create app container if it doesn't exist
    if (!document.getElementById('post-series-app')) {
        const appContainer = document.createElement('div');
        appContainer.id = 'post-series-app';
        document.body.appendChild(appContainer);
    }

    // Render React app
    const container = document.getElementById('post-series-app');
    const root = createRoot(container);
    root.render(<App />);
});