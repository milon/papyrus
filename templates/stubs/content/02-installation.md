---
title: "Installation"
---

# Installation

There are several ways to install Papyrus on your system.

## From Source

If you want to build from source:

```bash
git clone https://github.com/milon/papyrus
cd papyrus
cargo build --release
```

The binary will be available at `target/release/papyrus`.

## Using Cargo

The easiest way to install Papyrus is using Cargo:

```bash
cargo install papyrus
```

This will download, compile, and install Papyrus on your system.

## Verify Installation

After installation, verify that Papyrus is working:

```bash
papyrus --help
```

You should see the help menu with all available commands.
