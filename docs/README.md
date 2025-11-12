# Documentation Index

Welcome to the Laravel Resource Permissions documentation. This package extends Spatie Laravel Permission with resource-based permissions and roles.

## Getting Started

- **[Installation Guide](installation.md)** - How to install and set up the package
- **[Configuration](configuration.md)** - Configuration options and customization

## Usage

- **[Usage & API Reference](usage.md)** - Complete API documentation and usage examples
- **[Spatie Integration](integration.md)** - How this package works with Spatie permissions
- **[Examples](examples.md)** - Real-world examples and use cases

## Advanced Topics

- **[Advanced Usage](advanced.md)** - Advanced patterns and best practices
- **[Troubleshooting](troubleshooting.md)** - Common issues and solutions

## Overview

Laravel Resource Permissions allows you to:

- Assign permissions to users for specific resources (polymorphic)
- Assign roles to users for specific resources
- Check permissions and roles at the resource level
- Maintain compatibility with Spatie's global permission system

The package uses a separate `model_has_resource_and_permissions` table (configurable) that references Spatie's `permissions` and `roles` tables, ensuring full integration with your existing Spatie setup.

