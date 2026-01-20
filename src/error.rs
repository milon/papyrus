use thiserror::Error;

#[derive(Error, Debug)]
pub enum PapyrusError {
    #[error("IO error: {0}")]
    Io(#[from] std::io::Error),
    
    #[error("Configuration error: {0}")]
    Config(String),
    
    #[error("Markdown parsing error: {0}")]
    Markdown(String),
    
    #[error("Template error: {0}")]
    Template(#[from] tera::Error),
    
    #[error("YAML parsing error: {0}")]
    Yaml(#[from] serde_yaml::Error),
    
    #[error("TOML parsing error: {0}")]
    Toml(#[from] toml::de::Error),
    
    #[error("EPUB generation error: {0}")]
    #[allow(dead_code)]
    Epub(String),
    
    #[error("PDF generation error: {0}")]
    Pdf(String),
    
    #[error("Asset error: {0}")]
    Asset(String),
    
    #[error("ZIP error: {0}")]
    Zip(#[from] zip::result::ZipError),
    
    #[error("Walkdir error: {0}")]
    Walkdir(#[from] walkdir::Error),
}

pub type Result<T> = std::result::Result<T, PapyrusError>;
