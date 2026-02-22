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
          const basename = path.basename(src)

          // Ignorer les fichiers cachés
          if (basename.startsWith('.')) {
            if (this.options.verbose) {
              console.log(`Skipped hidden file: ${src}`)
            }
            return false
          }

          // Ignorer les fichiers .zip
          if (basename.toLowerCase().endsWith('.zip')) {
            if (this.options.verbose) {
              console.log(`Skipped zip file: ${src}`)
            }
            return false
          }

          // Ignorer les fichiers summernote-bs*
          if (basename.toLowerCase().includes('summernote-bs')) {
            if (this.options.verbose) {
              console.log(`Skipped summernote-bs file: ${src}`)
            }
            return false
          }

          return true
        }
      }

      try {
        if (fse.existsSync(module.from)) {
          fse.copySync(module.from, module.to, fseOptions)
        } else {
          fse.copySync(module.from.replace('node_modules/', '../'), module.to, fseOptions)
        }

        if (this.options.verbose) {
          console.log(`Copied ${module.from} to ${module.to}`)
        }

      } catch (error) {
        console.error(`Error: ${error}`)
      }
    })
  }
}

(new Publish()).run()
