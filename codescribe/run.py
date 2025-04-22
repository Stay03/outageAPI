#!/usr/bin/env python
import argparse
from src import CodebaseExtractor

def main():
    parser = argparse.ArgumentParser(description='Extract and document code from a codebase.')
    parser.add_argument('--base-dir', default="./", 
                        help='Base directory for extraction (default: current directory)')
    parser.add_argument('--output', default="./codebase_snapshot.md", 
                        help='Output file path (default: ./codebase_snapshot.md)')
    parser.add_argument('--config', default="codescribe/codebase_config.json", 
                        help='Configuration file path (default: codescribe/codebase_config.json)')
    parser.add_argument('--include', nargs='+', default=[],
                        help='Additional file or directory paths to include')
    
    args = parser.parse_args()
    
    extractor = CodebaseExtractor(
        base_directory=args.base_dir,
        output_file=args.output,
        config_file=args.config,
        include_paths=args.include
    )
    extractor.extract()

if __name__ == "__main__":
    main()