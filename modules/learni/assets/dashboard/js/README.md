# Learni Dashboard JavaScript Architecture

This directory contains the modular JavaScript architecture for the Learni Course Dashboard. 

To comply with the strict `< 500 lines` rule (`AGENT_GUIDELINES.md`), the monolithic `course-dashboard.js` was decomposed into smaller, specialized modules.

## Directory Structure

```text
js/
├── core/
│   └── dom-utils.js         # Core DOM manipulation, sanitization, and helper functions.
└── components/
    ├── word-counters.js     # Textarea word and character counting logic.
    ├── media-picker.js      # Integration with WordPress Media Uploader for cover images.
    ├── course-editor.js     # Course title, slug generation, and general metadata handling.
    └── lesson-editor.js     # Lesson creation, dragging, and management within the course builder.
```

## Loading Assets

Since this project does not use a bundler (like Webpack or Vite), all of these scripts are enqueued individually via PHP in `modules/learni/includes/Dashboard/class-creator-dashboard.php`. 

They are attached to the global scope to interact with each other. If adding new modules, ensure they are also added to the PHP `wp_enqueue_script` queue in the correct dependency order.
