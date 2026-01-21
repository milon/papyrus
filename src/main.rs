use clap::Parser;
use anyhow::Result;

mod cli;
mod config;
mod markdown;
mod html;
mod epub;
mod pdf;
mod assets;
mod error;

use cli::Cli;

#[tokio::main]
async fn main() -> Result<()> {
    let cli = Cli::parse();
    
    match cli.command {
        cli::Commands::Init { path } => {
            cli::init_command(path).await?;
        }
        cli::Commands::Pdf { theme, content, book_dir } => {
            cli::pdf_command(theme, content, book_dir).await?;
        }
        cli::Commands::Epub { content, book_dir } => {
            cli::epub_command(content, book_dir).await?;
        }
        cli::Commands::Html { content, book_dir } => {
            cli::html_command(content, book_dir).await?;
        }
        cli::Commands::Sample { theme } => {
            cli::sample_command(theme).await?;
        }
    }
    
    Ok(())
}
