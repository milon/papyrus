use clap::{Parser, Subcommand};
use anyhow::Result;
use std::path::PathBuf;

#[derive(Parser)]
#[command(name = "papyrus")]
#[command(about = "A Rust tool that helps you write eBooks in markdown and convert to PDF, EPUB and HTML")]
pub struct Cli {
    #[command(subcommand)]
    pub command: Commands,
}

#[derive(Subcommand)]
pub enum Commands {
    /// Initialize a new book project
    Init {
        /// Path where to initialize the book (default: current directory)
        #[arg(default_value = ".")]
        path: PathBuf,
    },
    /// Generate a PDF eBook
    Pdf {
        /// Theme to use (light or dark)
        #[arg(default_value = "light")]
        theme: String,
        /// Content directory path
        #[arg(short, long)]
        content: Option<PathBuf>,
        /// Book directory (where assets and config are located)
        #[arg(short, long)]
        book_dir: Option<PathBuf>,
    },
    /// Generate an EPUB eBook
    Epub {
        /// Content directory path
        #[arg(short, long)]
        content: Option<PathBuf>,
        /// Book directory (where assets and config are located)
        #[arg(short, long)]
        book_dir: Option<PathBuf>,
    },
    /// Generate an HTML eBook
    Html {
        /// Content directory path
        #[arg(short, long)]
        content: Option<PathBuf>,
        /// Book directory (where assets and config are located)
        #[arg(short, long)]
        book_dir: Option<PathBuf>,
    },
    /// Generate a sample PDF
    Sample {
        /// Theme to use (light or dark)
        #[arg(default_value = "light")]
        theme: String,
    },
}

pub async fn init_command(path: PathBuf) -> Result<()> {
    use crate::config;
    config::init_project(&path).await?;
    println!("Initialized new book project at: {}", path.display());
    Ok(())
}

pub async fn pdf_command(theme: String, content: Option<PathBuf>, book_dir: Option<PathBuf>) -> Result<()> {
    use crate::pdf;
    let book_dir = book_dir.unwrap_or_else(|| PathBuf::from("."));
    let content_dir = content.unwrap_or_else(|| book_dir.join("content"));
    
    pdf::generate_pdf(&book_dir, &content_dir, &theme).await?;
    println!("PDF generated successfully!");
    Ok(())
}

pub async fn epub_command(content: Option<PathBuf>, book_dir: Option<PathBuf>) -> Result<()> {
    use crate::epub;
    let book_dir = book_dir.unwrap_or_else(|| PathBuf::from("."));
    let content_dir = content.unwrap_or_else(|| book_dir.join("content"));
    
    epub::generate_epub(&book_dir, &content_dir).await?;
    println!("EPUB generated successfully!");
    Ok(())
}

pub async fn html_command(content: Option<PathBuf>, book_dir: Option<PathBuf>) -> Result<()> {
    use crate::html;
    let book_dir = book_dir.unwrap_or_else(|| PathBuf::from("."));
    let content_dir = content.unwrap_or_else(|| book_dir.join("content"));
    
    html::generate_html(&book_dir, &content_dir).await?;
    println!("HTML generated successfully!");
    Ok(())
}

pub async fn sample_command(theme: String) -> Result<()> {
    use crate::pdf;
    let book_dir = PathBuf::from(".");
    let content_dir = PathBuf::from("content");
    
    // TODO: Implement sample generation logic
    pdf::generate_pdf(&book_dir, &content_dir, &theme).await?;
    println!("Sample PDF generated successfully!");
    Ok(())
}
