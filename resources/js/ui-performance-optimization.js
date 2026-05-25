/**
 * UI Performance Optimization Module
 * Reduces flickering and improves rendering performance
 */

(function(window) {
    'use strict';

    // Timer Manager - consolidates setInterval/setTimeout to prevent conflicts
    class TimerManager {
        constructor() {
            this.timers = new Map();
            this.priority = new Map();
        }

        register(key, callback, interval, priority = 'normal') {
            // Clear existing timer if present
            if (this.timers.has(key)) {
                clearInterval(this.timers.get(key));
            }

            // Batch updates with requestAnimationFrame
            const wrappedCallback = () => {
                if (priority === 'high') {
                    callback();
                } else {
                    requestAnimationFrame(callback);
                }
            };

            const timerId = setInterval(wrappedCallback, interval);
            this.timers.set(key, timerId);
            this.priority.set(key, priority);

            return timerId;
        }

        clear(key) {
            if (this.timers.has(key)) {
                clearInterval(this.timers.get(key));
                this.timers.delete(key);
                this.priority.delete(key);
            }
        }

        clearAll() {
            this.timers.forEach(timerId => clearInterval(timerId));
            this.timers.clear();
            this.priority.clear();
        }
    }

    // Animation Performance Optimizer
    class AnimationOptimizer {
        static enableGPUAcceleration(element) {
            if (!element) return;
            element.style.willChange = 'transform';
            element.style.backfaceVisibility = 'hidden';
            element.style.transform = 'translateZ(0)';
        }

        static disableGPUAcceleration(element) {
            if (!element) return;
            element.style.willChange = 'auto';
            element.style.backfaceVisibility = 'visible';
            element.style.transform = 'none';
        }

        static batchDOMUpdates(updates) {
            // Read phase
            const data = {};
            for (const [key, readFn] of Object.entries(updates.read || {})) {
                data[key] = readFn();
            }

            // Write phase (using requestAnimationFrame)
            requestAnimationFrame(() => {
                for (const [key, writeFn] of Object.entries(updates.write || {})) {
                    writeFn(data[key]);
                }
            });
        }
    }

    // DOM Update Optimizer - debounces and batches DOM changes
    class DOMUpdateOptimizer {
        constructor() {
            this.queue = [];
            this.isScheduled = false;
            this.batchSize = 50;
        }

        queue(update) {
            this.queue.push(update);
            this.scheduleFlush();
        }

        scheduleFlush() {
            if (!this.isScheduled) {
                this.isScheduled = true;
                requestAnimationFrame(() => this.flush());
            }
        }

        flush() {
            const batch = this.queue.splice(0, this.batchSize);
            batch.forEach(update => update());
            
            if (this.queue.length > 0) {
                this.scheduleFlush();
            } else {
                this.isScheduled = false;
            }
        }

        clear() {
            this.queue = [];
            this.isScheduled = false;
        }
    }

    // Performance Monitor
    class PerformanceMonitor {
        static measure(name, fn) {
            const start = performance.now();
            const result = fn();
            const duration = performance.now() - start;
            
            if (duration > 16.67) { // Slower than 60fps
                console.warn(`[Performance] ${name} took ${duration.toFixed(2)}ms`);
            }
            
            return result;
        }

        static observeLongTasks(callback) {
            if ('PerformanceObserver' in window) {
                try {
                    const observer = new PerformanceObserver((list) => {
                        for (const entry of list.getEntries()) {
                            callback(entry);
                        }
                    });
                    observer.observe({ entryTypes: ['longtask'] });
                } catch (e) {
                    console.log('PerformanceObserver not supported');
                }
            }
        }
    }

    // Reduce CSS Animation Flicker
    class CSSOptimizer {
        static addOptimizedAnimationStyles() {
            if (document.getElementById('cssOptimizations')) return;

            const style = document.createElement('style');
            style.id = 'cssOptimizations';
            style.textContent = `
                /* Reduce flickering animations */
                [class*="animation"],
                [class*="animate"] {
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                    transform: translateZ(0);
                    backface-visibility: hidden;
                }

                /* Ensure buttons render smoothly */
                button {
                    will-change: auto;
                    transition: opacity 0.2s ease-out, transform 0.2s ease-out;
                }

                /* Optimize form inputs */
                input:focus,
                textarea:focus,
                select:focus {
                    will-change: auto;
                }

                /* Prevent layout shifts */
                img {
                    vertical-align: middle;
                    display: block;
                }
            `;
            document.head.appendChild(style);
        }
    }

    // Export to window
    window.UIPerformance = {
        TimerManager: new TimerManager(),
        AnimationOptimizer: AnimationOptimizer,
        DOMUpdateOptimizer: new DOMUpdateOptimizer(),
        PerformanceMonitor: PerformanceMonitor,
        CSSOptimizer: CSSOptimizer
    };

    // Initialize optimizations
    document.addEventListener('DOMContentLoaded', () => {
        CSSOptimizer.addOptimizedAnimationStyles();
        console.log('UI Performance Optimizations initialized');
    });

})(window);
