/**
 * CrearLibroModal Component - Sistema Biblioteca
 * 
 * Aplicando la sabiduría colectiva de 50+ educadores influyentes:
 * - Kent C. Dodds: Testing-focused development y robust form handling
 * - Robert C. Martin: Clean Code principles y single responsibility
 * - Jonas Schmedtmann: Theory-practice integration con beautiful UI
 * - Sarah Drasner: Visual excellence y micro-animations
 * - Dan Abramov: Transparent learning y hook patterns
 * 
 * @author Sistema Biblioteca - CRUD Implementation
 * @version 1.0.0
 */

import React, { useState } from 'react';
import { 
  BookOpenIcon, 
  ExclamationCircleIcon,
  CheckCircleIcon,
  ArrowPathIcon
} from '@heroicons/react/24/outline';
import Modal from '../common/Modal';
import { LibroFormData } from '../../types';
import apiClient from '../../services/api';

// ===== INTERFACES =====
interface CrearLibroModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void; // Callback para refrescar lista
}

// Martin Fowler's type safety - Explicit index signature for dynamic access
interface FormErrors {
  titulo?: string;
  autor?: string;
  genero?: string;
  isbn?: string;
  descripcion?: string;
  [key: string]: string | undefined; // Allow dynamic indexing
}

interface FormState {
  data: LibroFormData;
  errors: FormErrors;
  touched: Record<string, boolean>;
  isSubmitting: boolean;
  submitSuccess: boolean;
  submitError: string | null;
}

// ===== CONSTANTS =====
const GENEROS_DISPONIBLES = [
  'Ficción',
  'No ficción',
  'Ciencia ficción',
  'Fantasía',
  'Romance',
  'Misterio',
  'Biografía',
  'Historia',
  'Ciencia',
  'Tecnología',
  'Arte',
  'Filosofía',
  'Educación',
  'Infantil',
  'Otro'
];

const INITIAL_FORM_STATE: FormState = {
  data: {
    titulo: '',
    autor: '',
    genero: '',
    isbn: '',
    descripcion: '',
    disponible: true
  },
  errors: {},
  touched: {},
  isSubmitting: false,
  submitSuccess: false,
  submitError: null
};

// ===== COMPONENT =====
const CrearLibroModal: React.FC<CrearLibroModalProps> = ({
  isOpen,
  onClose,
  onSuccess
}) => {
  const [formState, setFormState] = useState<FormState>(INITIAL_FORM_STATE);

  // ===== VALIDATION FUNCTIONS =====
  // Aplicando Kent C. Dodds' robust validation approach
  
  const validateField = (name: keyof LibroFormData, value: string): string | undefined => {
    switch (name) {
      case 'titulo':
        if (!value.trim()) return 'El título es obligatorio';
        if (value.length > 255) return 'El título no puede exceder 255 caracteres';
        break;
        
      case 'autor':
        if (!value.trim()) return 'El autor es obligatorio';
        if (value.length > 255) return 'El autor no puede exceder 255 caracteres';
        break;
        
      case 'genero':
        if (!value.trim()) return 'El género es obligatorio';
        if (!GENEROS_DISPONIBLES.includes(value)) return 'Selecciona un género válido';
        break;
        
      case 'isbn':
        if (value && !/^(?:\d{10}|\d{13})$/.test(value.replace(/-/g, ''))) {
          return 'El ISBN debe tener 10 o 13 dígitos';
        }
        break;
        
      case 'descripcion':
        if (value && value.length > 1000) return 'La descripción no puede exceder 1000 caracteres';
        break;
    }
    return undefined;
  };

  const validateForm = (): FormErrors => {
    const errors: FormErrors = {};
    
    (Object.keys(formState.data) as Array<keyof LibroFormData>).forEach(field => {
      const error = validateField(field, formState.data[field] as string);
      if (error) errors[field] = error;
    });
    
    return errors;
  };

  // ===== EVENT HANDLERS =====
  
  const handleInputChange = (field: keyof LibroFormData, value: string | boolean) => {
    setFormState(prev => ({
      ...prev,
      data: { ...prev.data, [field]: value },
      touched: { ...prev.touched, [field]: true },
      errors: {
        ...prev.errors,
        [field]: typeof value === 'string' ? validateField(field, value) : undefined
      },
      submitError: null // Clear submit error on input change
    }));
  };

  const handleBlur = (field: keyof LibroFormData) => {
    setFormState(prev => ({
      ...prev,
      touched: { ...prev.touched, [field]: true }
    }));
  };

  // Robert C. Martin's Clean Code: Clear, single-purpose functions
  const resetForm = () => {
    setFormState(INITIAL_FORM_STATE);
  };

  const handleClose = () => {
    resetForm();
    onClose();
  };

  // Jonas Schmedtmann's theory-practice integration: Robust form submission
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    // Validate all fields
    const errors = validateForm();
    const hasErrors = Object.keys(errors).length > 0;
    
    setFormState(prev => ({
      ...prev,
      errors,
      touched: Object.keys(prev.data).reduce((acc, key) => ({
        ...acc,
        [key]: true
      }), {})
    }));
    
    if (hasErrors) return;
    
    // Submit form
    setFormState(prev => ({ ...prev, isSubmitting: true, submitError: null }));
    
    try {
      // Clean form data - remove empty optional fields
      const cleanData: LibroFormData = {
        titulo: formState.data.titulo.trim(),
        autor: formState.data.autor.trim(),
        genero: formState.data.genero,
        disponible: formState.data.disponible
      };
      
      if (formState.data.isbn?.trim()) {
        cleanData.isbn = formState.data.isbn.trim();
      }
      
      if (formState.data.descripcion?.trim()) {
        cleanData.descripcion = formState.data.descripcion.trim();
      }
      
      console.log('🚀 Submitting libro data:', cleanData);
      
      const response = await apiClient.post('/libros', cleanData);
      
      console.log('✅ Libro created successfully:', response);
      
      setFormState(prev => ({
        ...prev,
        isSubmitting: false,
        submitSuccess: true
      }));
      
      // Show success state briefly, then close and refresh
      setTimeout(() => {
        onSuccess(); // Refresh parent list
        handleClose();
      }, 1500);
      
    } catch (error: any) {
      console.error('❌ Error creating libro:', error);
      
      let errorMessage = 'Error al crear el libro';
      
      if (error.details?.errors) {
        // Laravel validation errors
        const validationErrors: FormErrors = {};
        Object.entries(error.details.errors).forEach(([field, messages]) => {
          validationErrors[field as keyof FormErrors] = (messages as string[])[0];
        });
        
        setFormState(prev => ({
          ...prev,
          errors: { ...prev.errors, ...validationErrors },
          isSubmitting: false
        }));
        return;
      }
      
      setFormState(prev => ({
        ...prev,
        isSubmitting: false,
        submitError: error.message || errorMessage
      }));
    }
  };

  // ===== RENDER HELPERS =====
  
  const renderField = (
    field: keyof LibroFormData,
    label: string,
    type: 'text' | 'textarea' | 'select' = 'text',
    options?: string[],
    required = true
  ) => {
    const value = formState.data[field] as string;
    const error = formState.errors[field];
    const isTouched = formState.touched[field];
    const showError = error && isTouched;
    
    return (
      <div className="form-group">
        <label className="form-label">
          {label}
          {required && <span className="text-red-500 ml-1">*</span>}
        </label>
        
        {type === 'textarea' ? (
          <textarea
            value={value}
            onChange={(e) => handleInputChange(field, e.target.value)}
            onBlur={() => handleBlur(field)}
            className={`form-textarea ${showError ? 'border-red-500 focus:border-red-500' : ''}`}
            rows={3}
            placeholder={`Ingresa ${label.toLowerCase()}...`}
            disabled={formState.isSubmitting}
          />
        ) : type === 'select' ? (
          <select
            value={value}
            onChange={(e) => handleInputChange(field, e.target.value)}
            onBlur={() => handleBlur(field)}
            className={`form-select ${showError ? 'border-red-500 focus:border-red-500' : ''}`}
            disabled={formState.isSubmitting}
          >
            <option value="">Selecciona {label.toLowerCase()}</option>
            {options?.map(option => (
              <option key={option} value={option}>{option}</option>
            ))}
          </select>
        ) : (
          <input
            type="text"
            value={value}
            onChange={(e) => handleInputChange(field, e.target.value)}
            onBlur={() => handleBlur(field)}
            className={`form-input ${showError ? 'border-red-500 focus:border-red-500' : ''}`}
            placeholder={`Ingresa ${label.toLowerCase()}...`}
            disabled={formState.isSubmitting}
          />
        )}
        
        {showError && (
          <div className="form-error">
            <ExclamationCircleIcon className="w-4 h-4" />
            {error}
          </div>
        )}
        
        {/* Character count for long fields */}
        {(field === 'titulo' || field === 'autor') && value && (
          <div className="text-xs text-gray-500 mt-1">
            {value.length}/255 caracteres
          </div>
        )}
        
        {field === 'descripcion' && value && (
          <div className="text-xs text-gray-500 mt-1">
            {value.length}/1000 caracteres
          </div>
        )}
      </div>
    );
  };

  // Sarah Drasner's visual excellence: Success state animation
  const renderSuccessState = () => (
    <div className="text-center py-8">
      <div className="animate-bounce mb-4">
        <CheckCircleIcon className="w-16 h-16 text-green-500 mx-auto" />
      </div>
      <h3 className="text-xl font-semibold text-gray-900 mb-2">
        ¡Libro Creado Exitosamente!
      </h3>
      <p className="text-gray-600">
        El libro "{formState.data.titulo}" ha sido añadido a la biblioteca
      </p>
    </div>
  );

  // ===== MAIN RENDER =====
  
  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title="Crear Nuevo Libro"
      size="lg"
    >
      {formState.submitSuccess ? (
        renderSuccessState()
      ) : (
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Header Icon */}
          <div className="flex items-center space-x-3 pb-4 border-b border-gray-200">
            <div className="p-2 bg-biblioteca-100 rounded-lg">
              <BookOpenIcon className="w-6 h-6 text-biblioteca-600" />
            </div>
            <div>
              <h3 className="text-lg font-medium text-gray-900">
                Información del Libro
              </h3>
              <p className="text-sm text-gray-600">
                Completa los detalles del nuevo libro
              </p>
            </div>
          </div>

          {/* Form Fields */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="md:col-span-2">
              {renderField('titulo', 'Título')}
            </div>
            
            <div>
              {renderField('autor', 'Autor')}
            </div>
            
            <div>
              {renderField('genero', 'Género', 'select', GENEROS_DISPONIBLES)}
            </div>
            
            <div>
              {renderField('isbn', 'ISBN', 'text', undefined, false)}
            </div>
            
            <div className="md:col-span-2">
              {renderField('descripcion', 'Descripción', 'textarea', undefined, false)}
            </div>
          </div>

          {/* Submit Error */}
          {formState.submitError && (
            <div className="form-error">
              <ExclamationCircleIcon className="w-5 h-5" />
              {formState.submitError}
            </div>
          )}

          {/* Form Actions */}
          <div className="flex justify-end space-x-3 pt-6 border-t border-gray-200">
            <button
              type="button"
              onClick={handleClose}
              className="btn btn-secondary"
              disabled={formState.isSubmitting}
            >
              Cancelar
            </button>
            
            <button
              type="submit"
              className="btn btn-primary min-w-[120px]"
              disabled={formState.isSubmitting}
            >
              {formState.isSubmitting ? (
                <>
                  <ArrowPathIcon className="w-4 h-4 mr-2 animate-spin" />
                  Creando...
                </>
              ) : (
                'Crear Libro'
              )}
            </button>
          </div>
        </form>
      )}
    </Modal>
  );
};

export default CrearLibroModal;