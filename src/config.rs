use serde::{Deserialize, Serialize};
use std::path::Path;
use crate::error::{PapyrusError, Result};
use std::fs;
use toml;

// Embed template files at compile time
const THEME_LIGHT_TEMPLATE: &str = include_str!("../templates/stubs/assets/theme-light.html");
const THEME_DARK_TEMPLATE: &str = include_str!("../templates/stubs/assets/theme-dark.html");
const THEME_HTML_TEMPLATE: &str = include_str!("../templates/stubs/assets/theme-html.html");
const STYLE_CSS_TEMPLATE: &str = include_str!("../templates/stubs/assets/style.css");
const COVER_IMAGE: &[u8] = include_bytes!("../templates/stubs/assets/images/cover.png");

// Sample content chapters
const SAMPLE_CHAPTER_01: &str = include_str!("../templates/stubs/content/01-introduction.md");
const SAMPLE_CHAPTER_02: &str = include_str!("../templates/stubs/content/02-installation.md");
const SAMPLE_CHAPTER_03: &str = include_str!("../templates/stubs/content/03-quick-start.md");
const SAMPLE_CHAPTER_04: &str = include_str!("../templates/stubs/content/04-configuration.md");
const SAMPLE_CHAPTER_05: &str = include_str!("../templates/stubs/content/05-writing-content.md");
const SAMPLE_CHAPTER_06: &str = include_str!("../templates/stubs/content/06-themes-and-styling.md");
const SAMPLE_CHAPTER_07: &str = include_str!("../templates/stubs/content/07-generating-ebooks.md");
const SAMPLE_CHAPTER_08: &str = include_str!("../templates/stubs/content/08-cover-images.md");

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
            cover: Some("cover.png".to_string()),
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
    
    // Create sample content files from templates
    fs::write(path.join("content").join("01-introduction.md"), SAMPLE_CHAPTER_01)?;
    fs::write(path.join("content").join("02-installation.md"), SAMPLE_CHAPTER_02)?;
    fs::write(path.join("content").join("03-quick-start.md"), SAMPLE_CHAPTER_03)?;
    fs::write(path.join("content").join("04-configuration.md"), SAMPLE_CHAPTER_04)?;
    fs::write(path.join("content").join("05-writing-content.md"), SAMPLE_CHAPTER_05)?;
    fs::write(path.join("content").join("06-themes-and-styling.md"), SAMPLE_CHAPTER_06)?;
    fs::write(path.join("content").join("07-generating-ebooks.md"), SAMPLE_CHAPTER_07)?;
    fs::write(path.join("content").join("08-cover-images.md"), SAMPLE_CHAPTER_08)?;
    
    // Create sample theme files from templates
    copy_template_file(path, "theme-light.html", THEME_LIGHT_TEMPLATE)?;
    copy_template_file(path, "theme-dark.html", THEME_DARK_TEMPLATE)?;
    copy_template_file(path, "theme-html.html", THEME_HTML_TEMPLATE)?;
    copy_template_file(path, "style.css", STYLE_CSS_TEMPLATE)?;
    
    // Copy default cover image
    let cover_path = path.join("assets").join("images").join("cover.png");
    fs::write(&cover_path, COVER_IMAGE)?;
    
    Ok(())
}

fn copy_template_file<P: AsRef<Path>>(path: P, filename: &str, content: &str) -> Result<()> {
    let assets_dir = path.as_ref().join("assets");
    fs::write(assets_dir.join(filename), content)?;
    Ok(())
}
