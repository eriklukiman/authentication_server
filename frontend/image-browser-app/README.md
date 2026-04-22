# Image Browser App

This project is an Angular application designed to browse and display images in a grid format. It utilizes the Bun runtime for efficient dependency management and performance.

## Features

- **Image Grid**: Displays images in a responsive grid layout.
- **Lazy Loading**: Images are loaded as they enter the viewport, improving performance.
- **Image Fetching**: Retrieves images based on client ID through a dedicated service.

## Project Structure

```
image-browser-app
├── src
│   ├── app
│   │   ├── components
│   │   │   └── image-grid
│   │   │       ├── image-grid.component.ts
│   │   │       ├── image-grid.component.html
│   │   │       └── image-grid.component.css
│   │   ├── services
│   │   │   └── image.service.ts
│   │   ├── app.component.ts
│   │   ├── app.component.html
│   │   ├── app.component.css
│   │   ├── app.config.ts
│   │   └── app.routes.ts
│   ├── index.html
│   ├── main.ts
│   └── styles.css
├── angular.json
├── package.json
├── bun.lockb
├── tsconfig.json
├── tsconfig.app.json
├── tsconfig.spec.json
├── .editorconfig
├── .gitignore
└── README.md
```

## Setup Instructions

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd image-browser-app
   ```

2. **Install dependencies**:
   ```bash
   bun install
   ```

3. **Run the application**:
   ```bash
   bun run start
   ```

4. **Open your browser** and navigate to `http://localhost:4200` to view the application.

## Usage

- The application allows users to browse images by client ID.
- Images are displayed in a grid format, with lazy loading implemented for better performance.

## Contributing

Contributions are welcome! Please open an issue or submit a pull request for any enhancements or bug fixes.