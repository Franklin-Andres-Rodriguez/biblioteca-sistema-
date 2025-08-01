/**
 * Modal Component - Sistema Biblioteca
 * 
 * Componente modal reutilizable aplicando:
 * - Sarah Drasner: Visual excellence y micro-animations
 * - Dan Abramov: Component composition y prop-based design
 * - Kent C. Dodds: Accessibility y keyboard interaction
 * 
 * @author Sistema Biblioteca - Modal Foundation
 * @version 1.0.0
 */

import React, { useEffect, useRef } from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';

// ===== INTERFACES =====
interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  showCloseButton?: boolean;
}

// ===== COMPONENT =====
const Modal: React.FC<ModalProps> = ({
  isOpen,
  onClose,
  title,
  children,
  size = 'md',
  showCloseButton = true
}) => {
  const modalRef = useRef<HTMLDivElement>(null);

  // ===== SIZE VARIANTS =====
  const sizeClasses = {
    sm: 'max-w-md',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl'
  };

  // ===== EFFECTS =====
  
  // Handle escape key press (Kent C. Dodds accessibility)
  useEffect(() => {
    const handleEscapeKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape' && isOpen) {
        onClose();
      }
    };

    if (isOpen) {
      document.addEventListener('keydown', handleEscapeKey);
      // Prevent body scroll when modal is open
      document.body.style.overflow = 'hidden';
    }

    return () => {
      document.removeEventListener('keydown', handleEscapeKey);
      document.body.style.overflow = 'unset';
    };
  }, [isOpen, onClose]);

  // Auto-focus modal content for accessibility
  useEffect(() => {
    if (isOpen && modalRef.current) {
      modalRef.current.focus();
    }
  }, [isOpen]);

  // ===== EVENT HANDLERS =====
  
  // Handle backdrop click
  const handleBackdropClick = (e: React.MouseEvent<HTMLDivElement>) => {
    if (e.target === e.currentTarget) {
      onClose();
    }
  };

  // ===== RENDER =====
  
  if (!isOpen) return null;

  return (
    <div className="modal-overlay">
      {/* Backdrop with blur effect (Sarah Drasner visual excellence) */}
      <div 
        className="modal-backdrop"
        onClick={handleBackdropClick}
        aria-hidden="true"
      />
      
      {/* Modal container */}
      <div className="modal-container">
        <div 
          ref={modalRef}
          className={`modal-content ${sizeClasses[size]}`}
          role="dialog"
          aria-modal="true"
          aria-labelledby="modal-title"
          tabIndex={-1}
        >
          {/* Header */}
          <div className="modal-header">
            <h2 
              id="modal-title"
              className="modal-title"
            >
              {title}
            </h2>
            
            {showCloseButton && (
              <button
                type="button"
                className="modal-close-button"
                onClick={onClose}
                aria-label="Cerrar modal"
              >
                <XMarkIcon className="w-5 h-5" />
              </button>
            )}
          </div>
          
          {/* Content */}
          <div className="modal-body">
            {children}
          </div>
        </div>
      </div>
      
      {/* Sarah Drasner's visual excellence - usando CSS classes de Tailwind */}
      <style>{`
        .modal-overlay {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          z-index: 50;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 1rem;
        }
        
        .modal-backdrop {
          position: absolute;
          inset: 0;
          background-color: rgba(0, 0, 0, 0.5);
          backdrop-filter: blur(4px);
          animation: fadeIn 0.2s ease-out;
        }
        
        .modal-container {
          position: relative;
          width: 100%;
          max-height: 90vh;
          overflow-y: auto;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        
        .modal-content {
          position: relative;
          width: 100%;
          background: white;
          border-radius: 0.75rem;
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
          animation: slideIn 0.3s ease-out;
          border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 1.5rem 1.5rem 1rem 1.5rem;
          border-bottom: 1px solid #e5e7eb;
        }
        
        .modal-title {
          font-size: 1.25rem;
          font-weight: 600;
          color: #111827;
          font-family: serif;
        }
        
        .modal-close-button {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 2rem;
          height: 2rem;
          border-radius: 0.375rem;
          border: none;
          background: transparent;
          color: #6b7280;
          cursor: pointer;
          transition: all 0.15s ease;
        }
        
        .modal-close-button:hover {
          background-color: #f3f4f6;
          color: #374151;
        }
        
        .modal-body {
          padding: 1.5rem;
        }
        
        /* Animations siguiendo Sarah Drasner principles */
        @keyframes fadeIn {
          from {
            opacity: 0;
          }
          to {
            opacity: 1;
          }
        }
        
        @keyframes slideIn {
          from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
          }
          to {
            opacity: 1;
            transform: translateY(0) scale(1);
          }
        }
        
        /* Mobile responsiveness */
        @media (max-width: 640px) {
          .modal-overlay {
            padding: 0.5rem;
          }
          
          .modal-content {
            border-radius: 0.5rem;
            max-height: 95vh;
          }
          
          .modal-header {
            padding: 1rem 1rem 0.75rem 1rem;
          }
          
          .modal-body {
            padding: 1rem;
          }
          
          .modal-title {
            font-size: 1.125rem;
          }
        }
      `}</style>
    </div>
  );
};

export default Modal;