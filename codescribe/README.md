# CodeScribe

A powerful tool for extracting and documenting codebases for LLM analysis.

## Features

- Extracts code from your entire codebase into a single, well-formatted document
- Configurable ignore patterns for files and directories
- Handles binary files appropriately
- Generates markdown output with proper syntax highlighting
- Includes metadata and file structure
- Customizable via JSON configuration

## Installation

Clone and install from source:

```bash
git clone https://github.com/Stay03/codescribe.git

```

## Usage

### Basic Usage

```bash
python codescribe/run.py
```

This will automatically create a codebase_snapshot.md file in your project directory containing all your codebase's documentation.

You can also use it programmatically in your code:

```python
extractor = CodebaseExtractor(
    base_directory="./your/project/path",
    output_file="codescribe/codebase_snapshot.md",
    config_file="codescribe/codebase_config.json"
)
extractor.extract()
```

## Configuration Options

- `ignore_patterns`: List of file/directory names to ignore
- `ignore_extensions`: List of file extensions to ignore
- `list_only_extensions`: List of file extensions to list without content
- `max_file_size_mb`: Maximum file size to process in MB