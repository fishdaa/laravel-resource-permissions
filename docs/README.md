# Documentation Index

Welcome to the Laravel Resource Permissions documentation. This package extends Spatie Laravel Permission with resource-based permissions and roles.

## Getting Started

- **[Introduction](introduction.md)** - What is resource permissions? Explained with real-world examples

- **[Installation Guide](installation.md)** - How to install and set up the package
- **[Configuration](configuration.md)** - Configuration options and customization
- **[UUID Setup](uuid-setup.md)** - How to configure the package to use UUIDs instead of integer IDs

## Usage

- **[Usage & API Reference](usage.md)** - Complete API documentation and usage examples
- **[Spatie Integration](integration.md)** - How this package works with Spatie permissions
- **[Examples](examples.md)** - Real-world examples and use cases

## Advanced Topics

- **[Advanced Usage](advanced.md)** - Advanced patterns and best practices
- **[Troubleshooting](troubleshooting.md)** - Common issues and solutions

## Overview

Laravel Resource Permissions allows you to:

- Assign permissions to any model (User, Team, Role, etc.) for specific resources (polymorphic)
- Assign roles to any model for specific resources
- Check permissions and roles at the resource level
- Maintain compatibility with Spatie's global permission system
- Support polymorphic relationships for maximum flexibility

The package uses a separate `model_has_resource_and_permissions` table (configurable) that references Spatie's `permissions` and `roles` tables, ensuring full integration with your existing Spatie setup.

### Polymorphic Support

The package uses polymorphic relationships, meaning any model can have resource permissions, not just users. This allows you to:

- Assign permissions to Users, Teams, Organizations, or any other model
- Use the same API for all model types
- Maintain backward compatibility with user-specific methods

