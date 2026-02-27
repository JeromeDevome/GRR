#!/usr/bin/env node

'use strict'

const path = require('path')
const fse = require('fs-extra')
const Plugins = require('./Plugins')

class Publish {
  constructor() {
    this.options = {
      verbose: false
    }

    this.getArguments()
  }

  getArguments() {
    if (process.argv.length > 2) {
      const arg = process.argv[2]
      switch (arg) {
        case '-v':
        case '--verbose':
          this.options.verbose = true
          break
        default:
          throw new Error(`Unknown option ${arg}`)
      }
    }
  }

  run() {

    // 🔥 Vider complètement le dossier plugins avant copie
    try {
      if (this.options.verbose) {
        console.log('Cleaning plugins directory...')
      }

      fse.emptyDirSync(path.resolve('jslib'))

      if (this.options.verbose) {
        console.log('jslib directory cleaned.')
      }

    } catch (error) {
      console.error(`Error cleaning jslib directory: ${error}`)
      return
    }

    // 📦 Copie des modules
    Plugins.forEach(module => {

      const fseOptions = {
        filter: (src) => {
          const basename = path.basename(src) // nom du fichier uniquement

          // Ignorer les fichiers cachés
          if (basename.startsWith('.')) return false

          // Ignorer les fichiers .zip
          if (basename.toLowerCase().endsWith('.zip')) return false

          // Ignorer les fichiers summernote-bs*
          if (basename.toLowerCase().includes('summernote-bs')) return false

          // Filtre personnalisé par module
          if (module.filterName) {
            // Si c'est un dossier, on le laisse passer
            if (!basename.includes('.') && fse.statSync(src).isDirectory()) return true
            // Sinon, il doit contenir filterName
            if (!basename.includes(module.filterName)) return false
          }

          return true
        }
      }

      try {
        const sourcePath = fse.existsSync(module.from) ? module.from : module.from.replace('node_modules/', '../')
        fse.copySync(sourcePath, module.to, fseOptions)

        if (this.options.verbose) {
          console.log(`Copied ${module.from} to ${module.to}`)
        }

      } catch (error) {
        console.error(`Error copying ${module.from} to ${module.to}: ${error}`)
      }
    })
  }
}

(new Publish()).run()
