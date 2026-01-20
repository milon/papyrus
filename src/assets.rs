use std::path::{Path, PathBuf};
use crate::error::{PapyrusError, Result};
use std::fs;

#[allow(dead_code)]
pub fn copy_assets<P: AsRef<Path>>(source: P, destination: P) -> Result<()> {
    let source = source.as_ref();
    let destination = destination.as_ref();
    
    if !source.exists() {
        return Err(PapyrusError::Asset(format!(
            "Source directory does not exist: {}",
            source.display()
        )));
    }
    
    fs::create_dir_all(destination)?;
    
    use walkdir::WalkDir;
    for entry in WalkDir::new(source) {
        let entry = entry?;
        let path = entry.path();
        
        if path.is_file() {
            let relative_path = path.strip_prefix(source)
                .map_err(|e| PapyrusError::Asset(format!("Failed to get relative path: {}", e)))?;
            let dest_path = destination.join(relative_path);
            
            if let Some(parent) = dest_path.parent() {
                fs::create_dir_all(parent)?;
            }
            
            fs::copy(path, &dest_path)?;
        }
    }
    
    Ok(())
}

#[allow(dead_code)]
pub fn ensure_export_directory<P: AsRef<Path>>(book_dir: P) -> Result<PathBuf> {
    let export_dir = book_dir.as_ref().join("export");
    fs::create_dir_all(&export_dir)?;
    Ok(export_dir)
}
