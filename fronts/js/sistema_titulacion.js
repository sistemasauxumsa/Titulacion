// Ejemplo de uso en JavaScript para el frontend

class SistemaTitulacion {
    constructor() {
        this.apiBase = 'api_titulacion.php';
    }

    // 1. Obtener carreras del estudiante
    async obtenerCarrerasEstudiante(curp) {
        try {
            const response = await fetch(`${this.apiBase}?action=carreras_estudiante&curp=${curp}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error al obtener carreras:', error);
            return { success: false, error: error.message };
        }
    }

    // 2. Obtener modalidades por grado académico
    async obtenerModalidadesPorGrado(idGrado) {
        try {
            const response = await fetch(`${this.apiBase}?action=modalidades&id_grado=${idGrado}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error al obtener modalidades:', error);
            return { success: false, error: error.message };
        }
    }

    // 3. Verificar si el estudiante ya tiene folio
    async verificarFolioExistente(curp) {
        try {
            const response = await fetch(`${this.apiBase}?action=folio_curp&curp=${curp}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error al verificar folio:', error);
            return { success: false, error: error.message };
        }
    }

    // 4. Crear nuevo folio
    async crearFolio(curp, idGradoAcademico, idCarrera, idModalidad) {
        try {
            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'crear_folio',
                    curp: curp,
                    id_grado_academico: idGradoAcademico,
                    id_carrera: idCarrera,
                    id_modalidad_tit: idModalidad
                })
            });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error al crear folio:', error);
            return { success: false, error: error.message };
        }
    }

    // 5. Obtener documentos requeridos (solo los faltantes)
    async obtenerDocumentosRequeridos(idFolio) {
        try {
            const response = await fetch(`${this.apiBase}?action=documentos_requeridos&id_folio=${idFolio}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error al obtener documentos requeridos:', error);
            return { success: false, error: error.message };
        }
    }

    // 6. Obtener estado completo de documentos
    async obtenerEstadoCompletoDocumentos(idFolio) {
        try {
            const response = await fetch(`${this.apiBase}?action=estado_completo_documentos&id_folio=${idFolio}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error al obtener estado completo:', error);
            return { success: false, error: error.message };
        }
    }

    // 7. Subir documento
    async subirDocumento(idFolio, idDocTit, rutaArchivo) {
        try {
            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'subir_documento',
                    id_folio: idFolio,
                    id_doc_tit: idDocTit,
                    ruta_archivo: rutaArchivo
                })
            });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error al subir documento:', error);
            return { success: false, error: error.message };
        }
    }

    // 8. Flujo completo para cargar documentos
    async cargarDocumentosPorModalidad(curp) {
        try {
            // Paso 1: Obtener carreras del estudiante
            const carreras = await this.obtenerCarrerasEstudiante(curp);
            if (!carreras.success) {
                return carreras;
            }

            // Paso 2: Verificar si ya tiene folio
            const folioExistente = await this.verificarFolioExistente(curp);
            
            let idFolio;
            if (folioExistente.success && folioExistente.data) {
                // Ya tiene folio
                idFolio = folioExistente.data.id;
            } else {
                // Necesita crear folio (asumimos primera carrera y modalidad)
                const primeraCarrera = carreras.data[0];
                const modalidades = await this.obtenerModalidadesPorGrado(primeraCarrera.id_grado);
                
                if (!modalidades.success || modalidades.data.length === 0) {
                    return { success: false, error: 'No hay modalidades disponibles' };
                }

                const nuevaModalidad = modalidades.data[0];
                const nuevoFolio = await this.crearFolio(
                    curp,
                    primeraCarrera.id_grado,
                    primeraCarrera.id,
                    nuevaModalidad.id
                );

                if (!nuevoFolio.success) {
                    return nuevoFolio;
                }

                idFolio = nuevoFolio.id_folio;
            }

            // Paso 3: Obtener documentos requeridos (solo faltantes)
            const documentosRequeridos = await this.obtenerDocumentosRequeridos(idFolio);
            
            // Paso 4: Obtener estado completo para mostrar todos los documentos
            const estadoCompleto = await this.obtenerEstadoCompletoDocumentos(idFolio);

            return {
                success: true,
                data: {
                    id_folio: idFolio,
                    carreras: carreras.data,
                    documentos_requeridos: documentosRequeridos.data,
                    estado_completo: estadoCompleto.data
                }
            };

        } catch (error) {
            console.error('Error en flujo completo:', error);
            return { success: false, error: error.message };
        }
    }
}

// Ejemplo de uso:
const sistema = new SistemaTitulacion();

// Función para mostrar documentos en el frontend
async function mostrarDocumentos(curp) {
    const resultado = await sistema.cargarDocumentosPorModalidad(curp);
    
    if (resultado.success) {
        const { documentos_requeridos, estado_completo } = resultado.data;
        
        // Mostrar solo documentos faltantes
        const documentosFaltantes = documentos_requeridos.filter(doc => !doc.subido);
        
        console.log('Documentos faltantes:', documentosFaltantes);
        console.log('Estado completo:', estado_completo);
        
        // Aquí puedes renderizar el HTML para mostrar los documentos
        renderizarDocumentos(documentosFaltantes, estado_completo);
    } else {
        console.error('Error:', resultado.error);
    }
}

function renderizarDocumentos(faltantes, completos) {
    const container = document.getElementById('documentos-container');
    
    // Mostrar documentos que faltan por subir
    faltantes.forEach(doc => {
        const div = document.createElement('div');
        div.className = 'documento-pendiente';
        div.innerHTML = `
            <h4>${doc.nombre}</h4>
            <input type="file" id="doc-${doc.id}" accept=".pdf,.jpg,.png">
            <button onclick="subirDocumento(${doc.id})">Subir Documento</button>
        `;
        container.appendChild(div);
    });
    
    // Mostrar documentos ya subidos
    completos.forEach(doc => {
        if (doc.id_documento_subido) {
            const div = document.createElement('div');
            div.className = 'documento-subido';
            div.innerHTML = `
                <h4>${doc.nombre_documento}</h4>
                <p>Estado: ${doc.estado_actual}</p>
                ${doc.fecha_subida ? `` : ''}
                ${doc.comentarios ? `<p>Comentarios: ${doc.comentarios}</p>` : ''}
            `;
            container.appendChild(div);
        }
    });
}
