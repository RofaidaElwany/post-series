# Post Series WordPress Plugin

A comprehensive WordPress plugin for managing and displaying post series with part numbering functionality, built with modern development practices including React integration.

## 🚀 Features

- **Series Management**: Create and manage post series with custom taxonomy
- **Part Numbering**: Automatic part numbering within series
- **Drag & Drop Ordering**: Sortable series parts with jQuery UI
- **React Integration**: Modern frontend components with React 18
- **Admin Interface**: User-friendly admin interface for series management
- **AJAX Functionality**: Dynamic content loading without page refreshes
- **Responsive Design**: Mobile-friendly interface

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Node.js 16 or higher (for development)
- Modern web browser with JavaScript enabled

## 🛠️ Installation

### From Source

1. Clone or download this repository
2. Upload the `post-series` folder to your WordPress `wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. For development, run `npm install` to install dependencies

### Development Setup

```bash
# Install dependencies
npm install

# Development mode with hot reloading
npm run start

# Build for production
npm run build
```

## 📁 Project Structure

```
post-series/
├── src/                    # Source files
│   ├── index.js           # React frontend component
│   ├── index.css          # Frontend styles
│   ├── admin.js           # Admin JavaScript functionality
│   └── style.css          # Additional styles
├── build/                 # Compiled build files (auto-generated)
├── post-series.php        # Main plugin file
├── package.json           # Node.js dependencies and scripts
├── .gitignore            # Git ignore rules
└── README.md             # This file
```

## 🔧 Usage

### Creating a Series

1. Go to your WordPress admin panel
2. Create or edit a post
3. In the "Series" meta box on the right side:
   - Select an existing series from the dropdown
   - Or click "Add New Series" to create a new one
4. The post will be automatically added to the series with proper ordering

### Managing Series Parts

- Posts in a series are automatically ordered
- Use drag & drop to reorder parts in the admin interface
- Each part gets a sequential number (Part 1, Part 2, etc.)

### Frontend Display

The plugin automatically adds React components to your pages where series functionality is needed.

## 🏗️ Architecture

### PHP Classes

- **`Post_Series`**: Main plugin class handling initialization and React asset enqueuing
- **`Assign_posts_to_a_series`**: Manages the series taxonomy registration
- **`Series_Meta_Box`**: Handles admin interface, AJAX endpoints, and series management

### JavaScript Components

- **React Frontend**: Modern component-based UI for public-facing features
- **Admin Interface**: jQuery-based admin functionality for series management
- **AJAX Handlers**: Dynamic content loading and series operations

## 🎯 Key Features Explained

### Series Taxonomy
- Custom WordPress taxonomy for organizing posts into series
- Non-hierarchical structure for flexible organization
- Admin column integration for easy series management

### Part Numbering System
- Automatic sequential numbering within each series
- Drag & drop reordering in admin interface
- Persistent ordering using WordPress post meta

### React Integration
- Modern frontend components
- Build system with WordPress Scripts
- Hot reloading for development
- Production-optimized builds

### Security
- WordPress nonce verification for all AJAX requests
- Proper capability checks for admin functions
- Sanitized input handling

## 🔨 Development

### Available Scripts

- `npm run start` - Development mode with hot reloading
- `npm run build` - Production build
- `npm run dev` - Alias for start command

### Building the Plugin

1. Make changes to source files in `src/`
2. Run `npm run build` to compile assets
3. The `build/` directory will contain optimized files
4. Test your changes in WordPress

### Code Organization

- **PHP**: Follows WordPress coding standards
- **JavaScript**: Modern ES6+ with React patterns
- **CSS**: Organized with component-based styling
- **Build**: Webpack-based with WordPress Scripts

## 🔌 WordPress Integration

### Hooks and Actions

- `init` - Series taxonomy registration
- `add_meta_boxes` - Admin interface setup
- `save_post` - Series assignment and ordering
- `admin_enqueue_scripts` - Asset loading
- `wp_enqueue_scripts` - Frontend asset loading

### AJAX Endpoints

- `add_new_series` - Create new series
- `get_series_parts` - Load series parts for ordering

## 📝 Changelog

### Version 1.0.0
- Initial release
- Series taxonomy implementation
- Admin interface with drag & drop
- React frontend integration
- AJAX functionality
- Part numbering system

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 👨‍💻 Author

**Rofaida** - Plugin Developer

## 🆘 Support

For support and questions:
- Check the WordPress admin interface
- Review the code documentation
- Test in a staging environment first

## 🔮 Future Enhancements

- Series navigation widgets
- Advanced series templates
- Series analytics and statistics
- Import/export functionality
- Multi-language support

---

**Note**: This plugin requires WordPress and PHP knowledge for customization. Always backup your site before making changes.