use serde::{Deserialize, Serialize};
use std::path::Path;
use crate::error::{PapyrusError, Result};
use std::fs;
use toml;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Config {
    pub title: String,
    pub author: String,
    pub language: Option<String>,
    pub cover: Option<String>,
    pub version: Option<String>,
    pub md_file_list: Option<Vec<String>>,
    pub sample: Option<SampleConfig>,
    pub fonts: Option<Vec<FontConfig>>,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SampleConfig {
    pub start_page: Option<u32>,
    pub end_page: Option<u32>,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct FontConfig {
    pub name: String,
    pub path: String,
}

impl Config {
    pub fn load<P: AsRef<Path>>(path: P) -> Result<Self> {
        let config_path = path.as_ref().join("papyrus.toml");
        
        if !config_path.exists() {
            return Err(PapyrusError::Config(format!(
                "Configuration file not found: {}",
                config_path.display()
            )));
        }
        
        let content = fs::read_to_string(&config_path)?;
        let config: Config = toml::from_str(&content)
            .map_err(|e| PapyrusError::Config(format!("Failed to parse config: {}", e)))?;
        
        Ok(config)
    }
    
    pub fn save<P: AsRef<Path>>(&self, path: P) -> Result<()> {
        let config_path = path.as_ref().join("papyrus.toml");
        let toml = toml::to_string_pretty(self)
            .map_err(|e| PapyrusError::Config(format!("Failed to serialize config: {}", e)))?;
        fs::write(&config_path, toml)?;
        Ok(())
    }
    
    pub fn default() -> Self {
        Config {
            title: "My Book".to_string(),
            author: "Author Name".to_string(),
            language: Some("en".to_string()),
            cover: None,
            version: Some("1.0.0".to_string()),
            md_file_list: None,
            sample: None,
            fonts: None,
        }
    }
}

pub async fn init_project<P: AsRef<Path>>(path: P) -> Result<()> {
    let path = path.as_ref();
    
    // Create directory structure
    fs::create_dir_all(path.join("content"))?;
    fs::create_dir_all(path.join("assets").join("fonts"))?;
    fs::create_dir_all(path.join("assets").join("images"))?;
    fs::create_dir_all(path.join("export"))?;
    
    // Create default config
    let config = Config::default();
    config.save(path)?;
    
    // Create sample content file
    let sample_content = r#"---
title: "Introduction"
---

# Introduction

Welcome to your new book!

This is a sample chapter. You can start writing your content here.

## Getting Started

Edit the files in the `content` directory to add your chapters.
"#;
    
    fs::write(path.join("content").join("01-introduction.md"), sample_content)?;
    
    // Create sample theme files
    create_default_theme_light(path)?;
    create_default_theme_dark(path)?;
    create_default_theme_html(path)?;
    create_default_style_css(path)?;
    
    Ok(())
}

fn create_default_theme_light<P: AsRef<Path>>(path: P) -> Result<()> {
    let theme = r#"<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ title }}</title>
    <style>
        body {
            font-family: 'Georgia', serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            color: #333;
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    {{ content }}
</body>
</html>"#;
    
    fs::write(path.as_ref().join("assets").join("theme-light.html"), theme)?;
    Ok(())
}

fn create_default_theme_dark<P: AsRef<Path>>(path: P) -> Result<()> {
    let theme = r#"<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ title }}</title>
    <style>
        body {
            font-family: 'Georgia', serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #1a1a1a;
            color: #e0e0e0;
        }
        h1, h2, h3 {
            color: #fff;
        }
        code {
            background: #2d2d2d;
            padding: 2px 6px;
            border-radius: 3px;
            color: #f8f8f2;
        }
        pre {
            background: #2d2d2d;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    {{ content }}
</body>
</html>"#;
    
    fs::write(path.as_ref().join("assets").join("theme-dark.html"), theme)?;
    Ok(())
}

fn create_default_theme_html<P: AsRef<Path>>(path: P) -> Result<()> {
    let theme = r#"<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ title }}</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        {{ content }}
    </div>
</body>
</html>"#;
    
    fs::write(path.as_ref().join("assets").join("theme-html.html"), theme)?;
    Ok(())
}

fn create_default_style_css<P: AsRef<Path>>(path: P) -> Result<()> {
    let css = r#"body {
    font-family: 'Georgia', serif;
    line-height: 1.6;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    color: #333;
}

h1, h2, h3 {
    color: #2c3e50;
}

code {
    background: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
}

pre {
    background: #f4f4f4;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
}

img {
    max-width: 100%;
    height: auto;
}
"#;
    
    fs::write(path.as_ref().join("assets").join("style.css"), css)?;
    Ok(())
}

pub async fn migrate_config<P: AsRef<Path>>(book_dir: P) -> Result<()> {
    // Check for old PHP config file
    let old_config_path = book_dir.as_ref().join("ibis.php");
    
    if old_config_path.exists() {
        // TODO: Parse PHP array config and convert to TOML
        // This would require parsing PHP code, which is complex
        // For now, we'll just inform the user
        println!("Found old ibis.php config file. Manual migration may be required.");
        println!("Please review the old config and create a papyrus.toml file.");
    } else {
        println!("No old configuration file found.");
    }
    
    Ok(())
}
