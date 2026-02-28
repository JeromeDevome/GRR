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
          const isDirectory = fse.statSync(src).isDirectory()

          // Toujours laisser passer les dossiers
          if (isDirectory) return true

          // ❌ Fichiers cachés
          if (basename.startsWith('.')) return false

          // ❌ Fichiers .zip
          if (basename.toLowerCase().endsWith('.zip')) return false

          // ✅ Filtre d'inclusion
          if (module.filterName) {
            const includes = Array.isArray(module.filterName)
              ? module.filterName.some(f => basename.includes(f))
              : basename.includes(module.filterName)

            if (!includes) return false
          }

          // 🚫 Filtre d'exclusion
          if (module.excludeName) {
            const excluded = Array.isArray(module.excludeName)
              ? module.excludeName.some(f => basename.includes(f))
              : basename.includes(module.excludeName)

            if (excluded) return false
          }

          return true
        }
      }

      try {
        const sourcePath = fse.existsSync(module.from)
          ? module.from
          : module.from.replace('node_modules/', '../')

        fse.copySync(sourcePath, module.to, fseOptions)

        if (this.options.verbose) {
          console.log(`Copied ${module.from} to ${module.to}`)
        }

      } catch (error) {
        console.error(`Error copying ${module.from}: ${error}`)
      }

    })
  }
}

(new Publish()).run()
